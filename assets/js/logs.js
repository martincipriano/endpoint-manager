/**
 * WPBuoy Endpoint Manager — Security Logs Filters
 *
 * Search/filter/pagination is AJAX-driven: every change re-queries the
 * server (ajax_search_logs() in the main plugin file) and swaps in the
 * returned HTML, instead of the old approach of hiding/showing whatever
 * rows happened to already be on the current page. That old approach could
 * only ever filter within a single page of results; this one searches the
 * full table. Browser history (back/forward, bookmarking, refresh) is kept
 * in sync via history.pushState()/popstate — see initLogsPopState().
 *
 * @package WPBuoy_Endpoint_Manager
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initLogsSearch();
        initLogsFilterVisibility();
        initLogsFilters();
        initLogsClearFilters();
        initLogsPagination();
        initLogsCellFilterLinks();
        initLogsPopState();

        // A bookmarked/shared filtered URL is already rendered correctly by
        // PHP on this same load — but "Found N entries" and search-term
        // highlighting are only wired up on the AJAX path. Re-running the
        // same query through that path once reconciles both without
        // duplicating either concern for the plain-page-load case. Skipped
        // entirely when nothing's filtered, so a normal unfiltered visit
        // costs no extra request.
        var initialFilters = gatherFilters();
        if (filtersAreActive(initialFilters)) {
            var initialParams = new URLSearchParams(window.location.search);
            var initialPaged = parseInt(initialParams.get('paged'), 10) || 1;
            fetchLogs(initialPaged, { pushState: false });
        }
    });

    /**
     * Initialize the "Filters" checkboxes in Screen Options (see
     * render_logs_filter_screen_settings() in the main plugin file). The
     * actual toggle mechanics live in filter-visibility.js.
     */
    function initLogsFilterVisibility() {
        if (!window.wpbyemInitFilterVisibility) return;

        window.wpbyemInitFilterVisibility({
            storageKey: 'wpbyem_logs_visible_filters',
            idMap: {
                ip: 'logs-ip-filter',
                endpoint: 'logs-endpoint-filter'
            },
            // "date" spans two control-groups (From and To), not the single
            // id-per-key idMap can express.
            resetOverride: function (key) {
                if (key !== 'date') return false;
                var dateFrom = document.getElementById('logs-date-from');
                var dateTo = document.getElementById('logs-date-to');
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                return true;
            },
            onChange: function () {
                fetchLogs(1, { pushState: true });
            }
        });
    }

    /**
     * Read the current value of every filter control. Select elements use
     * 'all' as their own "no filter" sentinel — normalized to '' here so
     * every caller (the AJAX request body, the pushState URL, the
     * has-active-filters check) agrees on what "unset" looks like, matching
     * the server-side convention in get_logs_filters_from_request().
     *
     * @return {Object}
     */
    function gatherFilters() {
        var searchInput = document.getElementById('logs-search');
        var ipFilter = document.getElementById('logs-ip-filter');
        var endpointFilter = document.getElementById('logs-endpoint-filter');
        var dateFrom = document.getElementById('logs-date-from');
        var dateTo = document.getElementById('logs-date-to');

        function val(el) {
            if (!el) return '';
            return el.value === 'all' ? '' : el.value;
        }

        return {
            search: searchInput ? searchInput.value.trim() : '',
            ip: val(ipFilter),
            endpoint: val(endpointFilter),
            dateFrom: dateFrom ? dateFrom.value : '',
            dateTo: dateTo ? dateTo.value : ''
        };
    }

    /**
     * @param {Object} filters
     * @return {boolean}
     */
    function filtersAreActive(filters) {
        return !!(filters.search || filters.ip || filters.endpoint || filters.dateFrom || filters.dateTo);
    }

    /**
     * The IP/Endpoint dropdowns' <option> lists are rendered once,
     * server-side, from whatever page of results was showing at the time
     * (see prepare_logs_view_data()) — they don't get refreshed by an AJAX
     * response. Setting a <select>'s value to something with no matching
     * <option> silently fails (the browser resets it to ''), which would
     * corrupt the very filter value it was supposed to set. This adds a
     * matching option on the fly when needed, so a value can always be
     * applied — from a cell-link click (initLogsCellFilterLinks()) or a
     * restored URL (initLogsPopState()) — regardless of whether that
     * dropdown happened to already know about it.
     *
     * @param {Element} select
     * @param {string}  value
     * @param {string}  [label]
     */
    function ensureOptionExists(select, value, label) {
        if (!select || !value) return;

        var exists = Array.prototype.some.call(select.options, function (opt) {
            return opt.value === value;
        });
        if (exists) return;

        var opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label !== undefined ? label : value;
        select.appendChild(opt);
    }

    /**
     * Turn a filters object + page number into the query-string entries
     * both the AJAX request body and the pushState URL use — one place so
     * the two can't drift on param names or on what counts as "unset".
     * `paged` is only included when > 1, keeping page-1 URLs clean.
     *
     * @param {Object} filters
     * @param {number} paged
     * @return {Array<[string, string]>}
     */
    function buildQueryEntries(filters, paged) {
        var entries = [];
        if (filters.search) entries.push(['filter_search', filters.search]);
        if (filters.ip) entries.push(['filter_ip', filters.ip]);
        if (filters.endpoint) entries.push(['filter_endpoint', filters.endpoint]);
        if (filters.dateFrom) entries.push(['filter_date_from', filters.dateFrom]);
        if (filters.dateTo) entries.push(['filter_date_to', filters.dateTo]);
        if (paged > 1) entries.push(['paged', String(paged)]);
        return entries;
    }

    /**
     * Fetch a page of (filtered) logs from the server and swap the table
     * rows + summary/pagination in place. This is the one path every
     * filter/search/pagination/cell-link interaction below funnels through.
     *
     * @param {number}  paged
     * @param {Object}  [options]
     * @param {boolean} [options.pushState=true] Push a new history entry for
     *        this state. Pass false when responding to popstate itself (the
     *        URL already reflects this state; pushing again would break Back).
     */
    // Bumped at the start of every fetchLogs() call; each call's .then()
    // only applies its response if it's still the most recently issued one
    // by the time that response lands. Without this, two overlapping
    // requests (e.g. a debounced search still in flight when a cell-link
    // click fires a second one) race — whichever *response* arrives last
    // wins the DOM swap and the pushState call, even if it was issued
    // first, silently reverting a more recent user action.
    var logsFetchToken = 0;

    function fetchLogs(paged, options) {
        options = options || {};
        var pushState = options.pushState !== false;

        var table = document.querySelector('.wpbuoy-em-logs-table');
        var tbody = table ? table.querySelector('tbody') : null;
        var actionsLeft = document.querySelector('.wpbuoy-em-logs-actions-left');
        if (!tbody || typeof wpbyemLogs === 'undefined') return;

        var thisToken = ++logsFetchToken;

        var filters = gatherFilters();
        var entries = buildQueryEntries(filters, paged);

        table.classList.add('is-loading');

        var body = new URLSearchParams();
        body.append('action', 'wpbyem_search_logs');
        body.append('nonce', wpbyemLogs.nonce);
        body.append('paged', String(paged));
        entries.forEach(function (entry) {
            if ('paged' !== entry[0]) body.append(entry[0], entry[1]);
        });

        fetch(wpbyemLogs.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (thisToken !== logsFetchToken) return; // superseded by a newer request — discard

                table.classList.remove('is-loading');
                if (!res.success) return;

                var data = res.data;
                tbody.innerHTML = data.rows_html;
                if (actionsLeft) actionsLeft.innerHTML = data.summary_html;

                highlightSearchTerm(filters.search);
                updateResultsInfo(data.has_active_filters, data.filtered_count);
                updateLogsClearButton(data.has_active_filters);

                if (pushState) {
                    var url = wpbyemLogs.logsPageUrl;
                    if (entries.length) {
                        // logsPageUrl is admin_url('admin.php?page=wpbyem-logs')
                        // — already has a '?', so appending our own query
                        // entries needs '&', not a second '?'.
                        var separator = url.indexOf('?') === -1 ? '?' : '&';
                        url += separator + entries.map(function (entry) {
                            return encodeURIComponent(entry[0]) + '=' + encodeURIComponent(entry[1]);
                        }).join('&');
                    }
                    history.pushState({ wpbyemLogsAjax: true }, '', url);
                }
            })
            .catch(function () {
                if (thisToken !== logsFetchToken) return;
                table.classList.remove('is-loading');
            });
    }

    /**
     * Highlight a search term across every row currently in the table.
     * Every row already matches the active filters (the server only sent
     * back matches) — this is purely "show where", not "hide non-matches"
     * like the old client-side-only filtering did.
     *
     * @param {string} term
     */
    function highlightSearchTerm(term) {
        var tbody = document.querySelector('.wpbuoy-em-logs-table tbody');
        if (!tbody) return;

        tbody.querySelectorAll('tr').forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length < 6) return;

            [cells[1], cells[2], cells[4]].forEach(function (cell) {
                if (term) {
                    highlightText(cell, term);
                } else {
                    removeHighlight(cell);
                }
            });
        });
    }

    /**
     * @param {boolean} hasActiveFilters
     * @param {number}  filteredCount
     */
    function updateResultsInfo(hasActiveFilters, filteredCount) {
        var resultsInfo = document.querySelector('.search-results-info');
        var resultsCount = document.querySelector('.search-results-count');
        if (!resultsInfo || !resultsCount) return;

        if (!hasActiveFilters) {
            resultsInfo.style.display = 'none';
            return;
        }

        resultsInfo.style.display = 'block';
        if (0 === filteredCount) {
            resultsCount.textContent = 'No entries found';
            resultsCount.className = 'search-results-count no-results';
        } else {
            var label = 1 === filteredCount ? 'entry' : 'entries';
            resultsCount.textContent = 'Found ' + filteredCount + ' ' + label;
            resultsCount.className = 'search-results-count has-results';
        }
    }

    /**
     * @param {boolean} hasActiveFilters
     */
    function updateLogsClearButton(hasActiveFilters) {
        var clearBtn = document.getElementById('logs-clear-filters');
        var controlsRow = clearBtn ? clearBtn.closest('.rest-api-controls-row') : null;
        if (!clearBtn) return;

        clearBtn.style.display = hasActiveFilters ? 'block' : 'none';

        if (controlsRow) {
            if (hasActiveFilters) {
                controlsRow.classList.remove('no-clear-button');
            } else {
                controlsRow.classList.add('no-clear-button');
            }
        }
    }

    /**
     * Initialize search input with debounce, clear button, and keyboard shortcuts.
     */
    function initLogsSearch() {
        var searchInput = document.getElementById('logs-search');
        var clearButton = document.getElementById('logs-search-clear');

        if (!searchInput) return;

        var searchTimeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            var term = searchInput.value.trim();

            clearButton.style.display = term ? 'flex' : 'none';

            searchTimeout = setTimeout(function () {
                fetchLogs(1, { pushState: true });
            }, 300);
        });

        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            clearButton.style.display = 'none';
            fetchLogs(1, { pushState: true });
            searchInput.focus();
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }

            if (e.key === 'Escape' && document.activeElement === searchInput) {
                if (searchInput.value) {
                    searchInput.value = '';
                    clearButton.style.display = 'none';
                    fetchLogs(1, { pushState: true });
                } else {
                    searchInput.blur();
                }
            }
        });
    }

    /**
     * Initialize dropdown and date filters with change listeners.
     */
    function initLogsFilters() {
        var ipFilter = document.getElementById('logs-ip-filter');
        var endpointFilter = document.getElementById('logs-endpoint-filter');
        var dateFrom = document.getElementById('logs-date-from');
        var dateTo = document.getElementById('logs-date-to');

        if (!ipFilter) return;

        [ipFilter, endpointFilter, dateFrom, dateTo].forEach(function (el) {
            if (el) {
                el.addEventListener('change', function () {
                    fetchLogs(1, { pushState: true });
                });
            }
        });
    }

    /**
     * Initialize the clear filters button.
     */
    function initLogsClearFilters() {
        var clearBtn = document.getElementById('logs-clear-filters');
        if (!clearBtn) return;

        clearBtn.addEventListener('click', function () {
            var searchInput = document.getElementById('logs-search');
            var clearSearch = document.getElementById('logs-search-clear');
            var ipFilter = document.getElementById('logs-ip-filter');
            var endpointFilter = document.getElementById('logs-endpoint-filter');
            var dateFrom = document.getElementById('logs-date-from');
            var dateTo = document.getElementById('logs-date-to');

            if (searchInput) { searchInput.value = ''; }
            if (clearSearch) { clearSearch.style.display = 'none'; }
            if (ipFilter) { ipFilter.value = 'all'; }
            if (endpointFilter) { endpointFilter.value = 'all'; }
            if (dateFrom) { dateFrom.value = ''; }
            if (dateTo) { dateTo.value = ''; }

            fetchLogs(1, { pushState: true });
        });
    }

    /**
     * Prev/Next pagination — delegated on the summary/pagination container
     * itself (not the individual links) since fetchLogs() replaces its
     * innerHTML on every page change; a listener on the links themselves
     * would be gone after the first click.
     */
    function initLogsPagination() {
        var container = document.querySelector('.wpbuoy-em-logs-actions-left');
        if (!container) return;

        container.addEventListener('click', function (e) {
            var link = e.target.closest('.wpbuoy-em-page-prev, .wpbuoy-em-page-next');
            if (!link || 'A' !== link.tagName) return; // disabled state renders as <button>, not <a>

            e.preventDefault();
            var page = parseInt(link.getAttribute('data-page'), 10);
            if (page) fetchLogs(page, { pushState: true });
        });
    }

    /**
     * IP / Endpoint cell links — delegated on the tbody for the same reason
     * as pagination (rows get replaced wholesale). Clicking one replaces the
     * whole filter set with just that one dimension, matching the plain
     * <a href> fallback (add_query_arg() builds a fresh URL from just that
     * filter, not merged with whatever was already active) so JS and no-JS
     * behavior agree.
     */
    function initLogsCellFilterLinks() {
        var tbody = document.querySelector('.wpbuoy-em-logs-table tbody');
        if (!tbody) return;

        tbody.addEventListener('click', function (e) {
            var link = e.target.closest('a[data-filter-ip], a[data-filter-endpoint]');
            if (!link) return;

            e.preventDefault();

            var searchInput = document.getElementById('logs-search');
            var clearSearch = document.getElementById('logs-search-clear');
            var ipFilter = document.getElementById('logs-ip-filter');
            var endpointFilter = document.getElementById('logs-endpoint-filter');
            var dateFrom = document.getElementById('logs-date-from');
            var dateTo = document.getElementById('logs-date-to');

            if (searchInput) { searchInput.value = ''; }
            if (clearSearch) { clearSearch.style.display = 'none'; }
            if (ipFilter) { ipFilter.value = 'all'; }
            if (endpointFilter) { endpointFilter.value = 'all'; }
            if (dateFrom) { dateFrom.value = ''; }
            if (dateTo) { dateTo.value = ''; }

            if (link.hasAttribute('data-filter-ip') && ipFilter) {
                var ip = link.getAttribute('data-filter-ip');
                ensureOptionExists(ipFilter, ip);
                ipFilter.value = ip;
            } else if (link.hasAttribute('data-filter-endpoint') && endpointFilter) {
                var endpoint = link.getAttribute('data-filter-endpoint');
                ensureOptionExists(endpointFilter, endpoint);
                endpointFilter.value = endpoint;
            }

            fetchLogs(1, { pushState: true });
        });
    }

    /**
     * Restore filter state from the URL on Back/Forward — the counterpart
     * to fetchLogs()'s pushState. Doesn't push a new entry itself (that
     * would break Back by re-adding what the user just navigated away from).
     */
    function initLogsPopState() {
        window.addEventListener('popstate', function () {
            var params = new URLSearchParams(window.location.search);

            var searchInput = document.getElementById('logs-search');
            var clearSearch = document.getElementById('logs-search-clear');
            var ipFilter = document.getElementById('logs-ip-filter');
            var endpointFilter = document.getElementById('logs-endpoint-filter');
            var dateFrom = document.getElementById('logs-date-from');
            var dateTo = document.getElementById('logs-date-to');

            var search = params.get('filter_search') || '';
            var ip = params.get('filter_ip') || '';
            var endpoint = params.get('filter_endpoint') || '';

            if (searchInput) { searchInput.value = search; }
            if (clearSearch) { clearSearch.style.display = search ? 'flex' : 'none'; }

            if (ipFilter) {
                ensureOptionExists(ipFilter, ip);
                ipFilter.value = ip || 'all';
            }
            if (endpointFilter) {
                ensureOptionExists(endpointFilter, endpoint);
                endpointFilter.value = endpoint || 'all';
            }
            if (dateFrom) { dateFrom.value = params.get('filter_date_from') || ''; }
            if (dateTo) { dateTo.value = params.get('filter_date_to') || ''; }

            var paged = parseInt(params.get('paged'), 10) || 1;
            fetchLogs(paged, { pushState: false });
        });
    }

    /**
     * Highlight matching text inside an element.
     *
     * @param {Element} element  Target element.
     * @param {string}  term     Search term.
     */
    function highlightText(element, term) {
        if (!element || !term) return;

        var original = element.getAttribute('data-original-text') || element.textContent;
        element.setAttribute('data-original-text', original);

        var regex = new RegExp('(' + escapeRegex(term) + ')', 'gi');
        element.innerHTML = original.replace(regex, '<mark>$1</mark>');
    }

    /**
     * Remove highlighting from an element.
     *
     * @param {Element} element Target element.
     */
    function removeHighlight(element) {
        if (!element) return;

        var original = element.getAttribute('data-original-text');
        if (original) {
            element.textContent = original;
            element.removeAttribute('data-original-text');
        }
    }

    /**
     * Escape special regex characters.
     *
     * @param {string} str Input string.
     * @returns {string} Escaped string.
     */
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
})();
