/**
 * REST API Manager Admin JavaScript
 *
 * @package RestApiManager
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initAccordion();
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
     * Expand all namespaces (for debugging or user convenience)
     */
    window.expandAllNamespaces = function() {
        const headers = document.querySelectorAll('.namespace-header:not(.expanded)');
        headers.forEach(function(header) {
            toggleNamespace(header);
        });
    };

    /**
     * Collapse all namespaces
     */
    window.collapseAllNamespaces = function() {
        const headers = document.querySelectorAll('.namespace-header.expanded');
        headers.forEach(function(header) {
            toggleNamespace(header);
        });
    };

    /**
     * Show a confirmation dialog when endpoints are being newly disabled.
     */
    function initSaveConfirmation() {
        const form = document.getElementById('wpbuoy-endpoint-manager-form');
        if (!form) return;

        // Snapshot which endpoints are already blocked on page load.
        const initialBlocked = new Set();
        form.querySelectorAll('input[name="wpbuoy_endpoint_manager_blocked_endpoints_encoded[]"]:checked').forEach(function(cb) {
            initialBlocked.add(cb.value);
        });

        form.addEventListener('submit', function(e) {
            let newlyDisabled = 0;
            form.querySelectorAll('input[name="wpbuoy_endpoint_manager_blocked_endpoints_encoded[]"]:checked').forEach(function(cb) {
                if (!initialBlocked.has(cb.value)) {
                    newlyDisabled++;
                }
            });

            if (newlyDisabled === 0) return;

            const label = newlyDisabled === 1 ? 'endpoint' : 'endpoints';
            if (!window.confirm(newlyDisabled + ' ' + label + ' will be disabled. Disabled endpoints may break WordPress functionality, plugins, or themes that depend on the REST API. Are you sure you want to continue?')) {
                e.preventDefault();
            }
        });
    }

})();
