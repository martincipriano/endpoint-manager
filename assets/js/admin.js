/**
 * WPBuoy Endpoint Manager Admin JavaScript
 *
 * @package RestApiManager
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initAccordion();
        initSaveConfirmation();
        initSearch();
        initFilters();
        initLogsPage();
        initPreviewModal();
    });

    /**
     * Initialize accordion functionality
     */
    function initAccordion() {
        const headers = document.querySelectorAll('.namespace-header');

        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                toggleNamespace(this);
            });
        });

        // Add keyboard support
        headers.forEach(function(header) {
            const button = header.querySelector('.namespace-toggle');
            button.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleNamespace(header);
                }
            });
        });
    }

    /**
     * Toggle namespace accordion section
     * @param {Element} header - The namespace header element
     */
    function toggleNamespace(header) {
        const isExpanded = header.classList.contains('expanded');
        const routes = header.nextElementSibling;
        const button = header.querySelector('.namespace-toggle');

        if (isExpanded) {
            // Collapse
            header.classList.remove('expanded');
            routes.classList.remove('expanded');
            button.setAttribute('aria-expanded', 'false');

            // Animate collapse
            routes.style.overflow = 'hidden';
            routes.style.maxHeight = routes.scrollHeight + 'px';
            requestAnimationFrame(function() {
                routes.style.maxHeight = '0px';
            });

            setTimeout(function() {
                routes.style.display = 'none';
                routes.style.maxHeight = '';
                routes.style.overflow = '';
            }, 300);

        } else {
            // Expand
            header.classList.add('expanded');
            routes.classList.add('expanded');
            button.setAttribute('aria-expanded', 'true');

            // Animate expansion
            routes.style.overflow = 'hidden';
            routes.style.display = 'block';
            const height = routes.scrollHeight;
            routes.style.maxHeight = '0px';

            requestAnimationFrame(function() {
                routes.style.maxHeight = height + 'px';
            });

            setTimeout(function() {
                routes.style.maxHeight = '';
                routes.style.overflow = '';
            }, 300);
        }
    }

    /**
     * Show a confirmation dialog when endpoints are being newly disabled.
     */
    function initSaveConfirmation() {
        const form = document.getElementById('wpbyem-form');
        if (!form) return;

        // Snapshot which endpoints are already blocked on page load.
        const initialBlocked = new Set();
        form.querySelectorAll('input[name="wpbyem_blocked_endpoints_encoded[]"]:checked').forEach(function(cb) {
            initialBlocked.add(cb.value);
        });

        form.addEventListener('submit', function(e) {
            let newlyDisabled = 0;
            form.querySelectorAll('input[name="wpbyem_blocked_endpoints_encoded[]"]:checked').forEach(function(cb) {
                if (!initialBlocked.has(cb.value)) {
                    newlyDisabled++;
                }
            });

            if (newlyDisabled === 0) return;

            if (!window.confirm('You are attempting to block ' + newlyDisabled + ' endpoint' + (newlyDisabled === 1 ? '' : 's') + ' which might affect functions that depend on them. You can re-enable them at any time. Save changes?')) {
                e.preventDefault();
            }
        });
    }



    /**
     * Initialize search functionality
     */
    function initSearch() {
        const searchInput = document.getElementById('rest-api-search');
        const clearButton = document.getElementById('rest-api-search-clear');
        const resultsInfo = document.querySelector('.search-results-info');

        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.trim();

            if (searchTerm) {
                clearButton.style.display = 'flex';
            } else {
                clearButton.style.display = 'none';
                resultsInfo.style.display = 'none';
            }

            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 300);
        });

        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            clearButton.style.display = 'none';
            resultsInfo.style.display = 'none';
            applyFilters();
            searchInput.focus();
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }

            if (e.key === 'Escape' && document.activeElement === searchInput) {
                if (searchInput.value) {
                    searchInput.value = '';
                    clearButton.style.display = 'none';
                    resultsInfo.style.display = 'none';
                    applyFilters();
                } else {
                    searchInput.blur();
                }
            }
        });
    }

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        const statusFilter = document.getElementById('status-filter');
        const methodFilter = document.getElementById('method-filter');
        const typeFilter = document.getElementById('type-filter');
        const namespaceFilter = document.getElementById('namespace-filter');
        const clearFiltersButton = document.getElementById('clear-filters');

        if (!statusFilter || !namespaceFilter) return;

        [statusFilter, methodFilter, typeFilter, namespaceFilter].forEach(function(filter) {
            if (filter) {
                filter.addEventListener('change', function() {
                    applyFilters();
                    updateClearFiltersButton();
                });
            }
        });

        clearFiltersButton.addEventListener('click', function() {
            statusFilter.value = 'all';
            if (methodFilter) methodFilter.value = 'all';
            if (typeFilter) typeFilter.value = 'all';
            namespaceFilter.value = 'all';
            applyFilters();
            updateClearFiltersButton();
        });

        updateClearFiltersButton();
    }

    /**
     * Update the clear filters button visibility
     */
    function updateClearFiltersButton() {
        const statusFilter = document.getElementById('status-filter');
        const methodFilter = document.getElementById('method-filter');
        const namespaceFilter = document.getElementById('namespace-filter');
        const clearFiltersButton = document.getElementById('clear-filters');
        const controlsRow = document.querySelector('.rest-api-controls-row');

        const typeFilter = document.getElementById('type-filter');
        const hasActiveFilters = statusFilter.value !== 'all' ||
                                 (methodFilter && methodFilter.value !== 'all') ||
                                 (typeFilter && typeFilter.value !== 'all') ||
                                 namespaceFilter.value !== 'all';

        if (clearFiltersButton) {
            clearFiltersButton.style.display = hasActiveFilters ? 'block' : 'none';
        }

        if (controlsRow) {
            if (hasActiveFilters) {
                controlsRow.classList.remove('no-clear-button');
            } else {
                controlsRow.classList.add('no-clear-button');
            }
        }
    }

    /**
     * Apply all active filters
     */
    function applyFilters() {
        const searchInput = document.getElementById('rest-api-search');
        const searchTerm = searchInput ? searchInput.value.trim() : '';

        const statusFilter = document.getElementById('status-filter');
        const methodFilter = document.getElementById('method-filter');
        const namespaceFilter = document.getElementById('namespace-filter');

        const typeFilter = document.getElementById('type-filter');
        const filters = {
            search: searchTerm,
            status: statusFilter ? statusFilter.value : 'all',
            method: methodFilter ? methodFilter.value : 'all',
            type: typeFilter ? typeFilter.value : 'all',
            namespace: namespaceFilter ? namespaceFilter.value : 'all'
        };

        performFiltering(filters);
    }

    /**
     * Perform filtering with all criteria
     * @param {Object} filters - The filter criteria
     */
    function performFiltering(filters) {
        const namespaces = document.querySelectorAll('.rest-api-namespace');
        const resultsInfo = document.querySelector('.search-results-info');
        const resultsCount = document.querySelector('.search-results-count');
        let totalMatches = 0;
        let visibleNamespaces = 0;

        namespaces.forEach(function(namespace) {
            const namespaceHeader = namespace.querySelector('.namespace-header h3');
            const namespaceName = namespaceHeader ? namespaceHeader.textContent.toLowerCase() : '';
            const routes = namespace.querySelectorAll('.rest-api-route');
            let namespaceMatches = 0;

            if (filters.namespace !== 'all' && filters.namespace !== namespaceHeader.textContent) {
                namespace.style.display = 'none';
                removeHighlight(namespaceHeader);
                return;
            }

            routes.forEach(function(route) {
                const routePath = route.querySelector('.route-path');
                const checkbox = route.querySelector('input[type="checkbox"]');

                if (!routePath) return;

                const pathText = routePath.textContent.toLowerCase();
                const methodsText = (route.getAttribute('data-methods') || '').toLowerCase();
                const isEnabled = checkbox ? !checkbox.checked : true;

                let matches = true;

                if (filters.search) {
                    matches = matches && (
                        pathText.includes(filters.search.toLowerCase()) ||
                        namespaceName.includes(filters.search.toLowerCase()) ||
                        methodsText.includes(filters.search.toLowerCase())
                    );
                }

                if (filters.status !== 'all') {
                    if (filters.status === 'enabled' && !isEnabled) matches = false;
                    if (filters.status === 'disabled' && isEnabled) matches = false;
                }

                if (filters.method !== 'all') {
                    const routeMethods = route.getAttribute('data-methods') || '';
                    if (!routeMethods.split(',').includes(filters.method)) matches = false;
                }

                if (filters.type !== 'all') {
                    const routeType = route.getAttribute('data-type') || 'static';
                    if (routeType !== filters.type) matches = false;
                }

                if (matches) {
                    route.style.display = 'flex';
                    namespaceMatches++;

                    if (filters.search) {
                        highlightText(routePath, filters.search);
                    } else {
                        removeHighlight(routePath);
                    }
                } else {
                    route.style.display = 'none';
                    removeHighlight(routePath);
                }
            });

            if (namespaceMatches > 0) {
                namespace.style.display = 'block';
                visibleNamespaces++;

                if (filters.search || filters.status !== 'all' || filters.method !== 'all' || filters.type !== 'all') {
                    const header = namespace.querySelector('.namespace-header');
                    const routesContainer = namespace.querySelector('.rest-api-routes');
                    if (header && routesContainer && !header.classList.contains('expanded')) {
                        toggleNamespace(header);
                    }
                }

                if (filters.search) {
                    highlightText(namespaceHeader, filters.search);
                } else {
                    removeHighlight(namespaceHeader);
                }
            } else {
                namespace.style.display = 'none';
                removeHighlight(namespaceHeader);
            }

            totalMatches += namespaceMatches;
        });

        const hasActiveFilters = filters.search || filters.status !== 'all' || filters.method !== 'all' || filters.type !== 'all' || filters.namespace !== 'all';

        if (hasActiveFilters) {
            resultsInfo.style.display = 'block';
            if (totalMatches === 0) {
                resultsCount.textContent = 'No endpoints found';
                resultsCount.className = 'search-results-count no-results';
            } else {
                const endpointText = totalMatches === 1 ? 'endpoint' : 'endpoints';
                const namespaceText = visibleNamespaces === 1 ? 'namespace' : 'namespaces';
                resultsCount.textContent = `Found ${totalMatches} ${endpointText} in ${visibleNamespaces} ${namespaceText}`;
                resultsCount.className = 'search-results-count has-results';
            }
        } else {
            resultsInfo.style.display = 'none';
        }
    }

    /**
     * Highlight matching text
     * @param {Element} element - The element to highlight in
     * @param {string} searchTerm - The term to highlight
     */
    function highlightText(element, searchTerm) {
        if (!element || !searchTerm) return;

        const originalText = element.getAttribute('data-original-text') || element.textContent;
        element.setAttribute('data-original-text', originalText);

        const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
        element.innerHTML = originalText.replace(regex, '<mark>$1</mark>');
    }

    /**
     * Remove highlighting from text
     * @param {Element} element - The element to remove highlighting from
     */
    function removeHighlight(element) {
        if (!element) return;

        const originalText = element.getAttribute('data-original-text');
        if (originalText) {
            element.textContent = originalText;
            element.removeAttribute('data-original-text');
        }
    }

    /**
     * Escape special regex characters
     * @param {string} string - String to escape
     * @returns {string} - Escaped string
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Initialize logs page filtering
     */
    function initLogsPage() {
        if (!document.getElementById('logs-search')) return;

        var searchInput    = document.getElementById('logs-search');
        var clearSearch    = document.getElementById('logs-search-clear');
        var ipFilter       = document.getElementById('logs-ip-filter');
        var endpointFilter = document.getElementById('logs-endpoint-filter');
        var dateFrom       = document.getElementById('logs-date-from');
        var dateTo         = document.getElementById('logs-date-to');
        var clearFilters   = document.getElementById('logs-clear-filters');

        var searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            clearSearch.style.display = this.value ? 'flex' : 'none';
            searchTimeout = setTimeout(applyLogsFilters, 300);
        });

        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            clearSearch.style.display = 'none';
            applyLogsFilters();
            searchInput.focus();
        });

        [ipFilter, endpointFilter, dateFrom, dateTo].forEach(function(el) {
            if (el) el.addEventListener('change', applyLogsFilters);
        });

        clearFilters.addEventListener('click', function() {
            searchInput.value = '';
            clearSearch.style.display = 'none';
            if (ipFilter) ipFilter.value = 'all';
            if (endpointFilter) endpointFilter.value = 'all';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            applyLogsFilters();
        });
    }

    /**
     * Apply all active logs filters
     */
    function applyLogsFilters() {
        var searchInput    = document.getElementById('logs-search');
        var ipFilter       = document.getElementById('logs-ip-filter');
        var endpointFilter = document.getElementById('logs-endpoint-filter');
        var dateFrom       = document.getElementById('logs-date-from');
        var dateTo         = document.getElementById('logs-date-to');
        var resultsInfo    = document.querySelector('.search-results-info');
        var resultsCount   = document.querySelector('.search-results-count');

        var search   = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var ip       = ipFilter ? ipFilter.value : 'all';
        var endpoint = endpointFilter ? endpointFilter.value : 'all';
        var from     = dateFrom ? dateFrom.value : '';
        var to       = dateTo ? dateTo.value : '';

        var rows    = document.querySelectorAll('.wpbuoy-em-logs-table tbody tr[data-timestamp]');
        var matches = 0;

        rows.forEach(function(row) {
            var cells       = row.querySelectorAll('td');
            var rowIp       = cells[1] ? cells[1].textContent.trim() : '';
            var rowEndpoint = cells[2] ? cells[2].textContent.trim() : '';
            var rowAgent    = cells[4] ? cells[4].textContent.trim() : '';
            var timestamp   = row.getAttribute('data-timestamp') || '';
            var rowDate     = timestamp.substring(0, 10);

            var visible = true;

            if (search) {
                visible = rowIp.toLowerCase().indexOf(search) !== -1 ||
                          rowEndpoint.toLowerCase().indexOf(search) !== -1 ||
                          rowAgent.toLowerCase().indexOf(search) !== -1;
            }

            if (visible && ip !== 'all') {
                visible = rowIp === ip;
            }

            if (visible && endpoint !== 'all') {
                visible = rowEndpoint === endpoint;
            }

            if (visible && from) {
                visible = rowDate >= from;
            }

            if (visible && to) {
                visible = rowDate <= to;
            }

            row.style.display = visible ? '' : 'none';
            if (visible) matches++;
        });

        var hasFilters = search || ip !== 'all' || endpoint !== 'all' || from || to;

        if (resultsInfo) {
            resultsInfo.style.display = hasFilters ? 'block' : 'none';
        }

        if (resultsCount && hasFilters) {
            if (matches === 0) {
                resultsCount.textContent = 'No logs found';
                resultsCount.className = 'search-results-count no-results';
            } else {
                resultsCount.textContent = 'Found ' + matches + ' ' + (matches === 1 ? 'entry' : 'entries');
                resultsCount.className = 'search-results-count has-results';
            }
        }
    }

    /**
     * Initialize static endpoint preview modal.
     */
    function initPreviewModal() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.route-preview:not([disabled])');
            if (!btn) return;
            e.preventDefault();
            var url = btn.getAttribute('data-preview-url');
            if (url) showResponseModal(url);
        });
    }

    /**
     * Fetch a static endpoint URL and display the JSON response in a modal.
     * @param {string} url
     */
    function showResponseModal(url) {
        closePreviewModal();

        var overlay = document.createElement('div');
        overlay.className = 'wpb-emp-preview-overlay';
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closePreviewModal();
        });

        var modal = document.createElement('div');
        modal.className = 'wpb-emp-preview-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Preview Endpoint');

        // Title
        var title = document.createElement('h3');
        title.className = 'wpb-emp-preview-title';
        title.innerHTML = '<span class="mr-3"><svg width="26px" height="18px" viewBox="0 0 26 18" xmlns="http://www.w3.org/2000/svg"><path d="M16.5906419,12.7121786 C17.5744077,11.6923929 18.0662907,10.4540357 18.0662907,8.99710714 C18.0662907,7.54017857 17.5734758,6.30278571 16.5878459,5.28492857 C15.6022161,4.26707143 14.4053354,3.75814286 12.997204,3.75814286 C11.5890726,3.75814286 10.393124,4.26803571 9.40935812,5.28782143 C8.42559225,6.30760714 7.93370932,7.54596429 7.93370932,9.00289286 C7.93370932,10.4598214 8.42652424,11.6972143 9.41215409,12.7150714 C10.3977839,13.7329286 11.5946646,14.2418571 13.002796,14.2418571 C14.4109274,14.2418571 15.606876,13.7319643 16.5906419,12.7121786 Z M10.6234288,11.4589286 C9.97103666,10.7839286 9.64484061,9.96428571 9.64484061,9 C9.64484061,8.03571429 9.97103666,7.21607143 10.6234288,6.54107143 C11.2758209,5.86607143 12.0680113,5.52857143 13,5.52857143 C13.9319887,5.52857143 14.7241791,5.86607143 15.3765712,6.54107143 C16.0289633,7.21607143 16.3551594,8.03571429 16.3551594,9 C16.3551594,9.96428571 16.0289633,10.7839286 15.3765712,11.4589286 C14.7241791,12.1339286 13.9319887,12.4714286 13,12.4714286 C12.0680113,12.4714286 11.2758209,12.1339286 10.6234288,11.4589286 Z M5.19055585,15.5532857 C2.84070162,13.9223571 1.11051634,11.7379286 0,9 C1.11051634,6.26207143 2.84008029,4.07764286 5.18869187,2.44671429 C7.53751055,0.815571429 10.1407622,0 12.9984467,0 C15.8559241,0 18.4595899,0.815571429 20.8094442,2.44671429 C23.1592984,4.07764286 24.8894837,6.26207143 26,9 C24.8894837,11.7379286 23.1599197,13.9223571 20.8113081,15.5532857 C18.4624894,17.1844286 15.8592378,18 13.0015533,18 C10.1440759,18 7.54041008,17.1844286 5.19055585,15.5532857 Z M19.4462553,14.1589286 C21.4034316,12.8839286 22.8997913,11.1642857 23.9353343,9 C22.8997913,6.83571429 21.4034316,5.11607143 19.4462553,3.84107143 C17.489079,2.56607143 15.3403272,1.92857143 13,1.92857143 C10.6596728,1.92857143 8.510921,2.56607143 6.55374468,3.84107143 C4.59656837,5.11607143 3.1002087,6.83571429 2.06466568,9 C3.1002087,11.1642857 4.59656837,12.8839286 6.55374468,14.1589286 C8.510921,15.4339286 10.6596728,16.0714286 13,16.0714286 C15.3403272,16.0714286 17.489079,15.4339286 19.4462553,14.1589286 Z" fill="currentColor" fill-rule="nonzero"/></svg></span><span>Preview Endpoint</span>';
        modal.appendChild(title);

        // URL display
        var route = document.createElement('div');
        route.className = 'wpb-emp-preview-route';
        var restPath = url.replace(/^https?:\/\/[^/]+\/wp-json/, '/wp-json');
        var seg = document.createElement('span');
        seg.className = 'wpb-emp-preview-segment';
        seg.textContent = restPath;
        route.appendChild(seg);
        modal.appendChild(route);

        // Response area
        var responseWrap = document.createElement('div');
        responseWrap.className = 'wpb-emp-preview-response-wrap';

        var loading = document.createElement('p');
        loading.className = 'wpb-emp-preview-loading';
        loading.textContent = 'Loading\u2026';
        responseWrap.appendChild(loading);

        modal.appendChild(responseWrap);

        // Actions
        var actions = document.createElement('div');
        actions.className = 'wpb-emp-preview-actions';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'button';
        closeBtn.textContent = 'Close';
        closeBtn.addEventListener('click', closePreviewModal);
        actions.appendChild(closeBtn);

        modal.appendChild(actions);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        trapFocus(modal);
        closeBtn.focus();

        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePreviewModal();
        });

        // Fetch the endpoint
        fetch(url, { credentials: 'include' })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function(result) {
                responseWrap.innerHTML = '';
                var pre = document.createElement('pre');
                pre.className = 'wpb-emp-preview-response' + (result.ok ? '' : ' wpb-emp-preview-response--error');
                pre.textContent = JSON.stringify(result.data, null, 2);
                responseWrap.appendChild(pre);
                closeBtn.focus();
            })
            .catch(function(err) {
                responseWrap.innerHTML = '';
                var msg = document.createElement('p');
                msg.className = 'wpb-emp-preview-error';
                msg.textContent = 'Could not load response: ' + err.message;
                responseWrap.appendChild(msg);
            });
    }

    /**
     * Trap focus within a modal element.
     * @param {Element} modal
     */
    function trapFocus(modal) {
        modal.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;
            var focusable = modal.querySelectorAll('input, button, [tabindex]:not([tabindex="-1"])');
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }

    /**
     * Close and remove the preview modal.
     */
    function closePreviewModal() {
        var existing = document.querySelector('.wpb-emp-preview-overlay');
        if (existing) existing.remove();
    }

})();
