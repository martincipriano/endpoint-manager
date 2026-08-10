/**
 * WPBuoy Endpoint Manager — shared "Filters" visibility-toggle behavior
 *
 * Powers the Screen Options "Filters" fieldset on the Logs page — see
 * render_logs_filter_screen_settings() in the main plugin file for the PHP
 * side that renders the checkboxes. Each checkbox shows/hides its matching
 * .control-group[data-filter-key] in the controls row, persisted per-browser
 * in localStorage (not server-side — this is a display preference, not
 * something that needs to sync across devices).
 *
 * @package WPBuoy_Endpoint_Manager
 */

window.wpbyemInitFilterVisibility = function (options) {
    'use strict';

    var STORAGE_KEY = options.storageKey;
    var idMap = options.idMap || {};
    var resetOverride = options.resetOverride;
    var onChange = options.onChange || function () {};

    var toggles = document.querySelectorAll('.wpbyem-filter-visibility-toggle');
    if (!toggles.length) return;

    function getStoredVisible() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function setStoredVisible(keys) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(keys));
        } catch (e) {
            // Storage unavailable (private browsing, quota) — the
            // checkboxes still work for this page load, just won't persist.
        }
    }

    function setFilterVisible(key, visible) {
        // querySelectorAll, not querySelector — a key can be shared by more
        // than one control-group (e.g. Logs' "date" spans From + To).
        var groups = document.querySelectorAll('.rest-api-controls-row [data-filter-key="' + key + '"]');
        groups.forEach(function (group) {
            group.style.display = visible ? '' : 'none';
        });
    }

    // A hidden filter shouldn't keep silently affecting results — reset its
    // control(s) back to "no filter" when it's toggled off. resetOverride
    // handles keys with page-specific reset logic (e.g. a pair of date
    // inputs); everything else falls back to the plain idMap lookup.
    function resetFilterValue(key) {
        if (resetOverride && resetOverride(key)) return;
        var input = idMap[key] ? document.getElementById(idMap[key]) : null;
        if (input) input.value = 'all';
    }

    // Apply a stored preference, if one exists, overriding the
    // server-rendered defaults.
    var stored = getStoredVisible();
    if (stored) {
        toggles.forEach(function (toggle) {
            var key = toggle.getAttribute('data-filter-key');
            var visible = stored.indexOf(key) !== -1;
            toggle.checked = visible;
            setFilterVisible(key, visible);
        });
    }

    toggles.forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            var key = toggle.getAttribute('data-filter-key');
            setFilterVisible(key, toggle.checked);
            if (!toggle.checked) {
                resetFilterValue(key);
            }

            var visibleKeys = [];
            toggles.forEach(function (t) {
                if (t.checked) visibleKeys.push(t.getAttribute('data-filter-key'));
            });
            setStoredVisible(visibleKeys);

            onChange();
        });
    });
};
