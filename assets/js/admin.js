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
        initAccordionControls();
        initSaveConfirmation();
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
            routes.style.maxHeight = routes.scrollHeight + 'px';
            requestAnimationFrame(function() {
                routes.style.maxHeight = '0px';
            });

            setTimeout(function() {
                routes.style.display = 'none';
                routes.style.maxHeight = '';
            }, 300);

        } else {
            // Expand
            header.classList.add('expanded');
            routes.classList.add('expanded');
            button.setAttribute('aria-expanded', 'true');

            // Animate expansion
            routes.style.display = 'block';
            const height = routes.scrollHeight;
            routes.style.maxHeight = '0px';

            requestAnimationFrame(function() {
                routes.style.maxHeight = height + 'px';
            });

            setTimeout(function() {
                routes.style.maxHeight = '';
            }, 300);
        }
    }

    /**
     * Wire up expand all / collapse all buttons.
     */
    function initAccordionControls() {
        var expandBtn   = document.getElementById('expand-all');
        var collapseBtn = document.getElementById('collapse-all');

        if (expandBtn) {
            expandBtn.addEventListener('click', function () {
                document.querySelectorAll('.namespace-header:not(.expanded)').forEach(function (header) {
                    toggleNamespace(header);
                });
            });
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                document.querySelectorAll('.namespace-header.expanded').forEach(function (header) {
                    toggleNamespace(header);
                });
            });
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


})();
