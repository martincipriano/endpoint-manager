/**
 * WPBuoy Endpoint Manager — Settings Page
 *
 * @package Wpbyem_Endpoint_Manager
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
	});

	function initTabs() {
		var buttons = document.querySelectorAll('.wpbyem-tab-button');
		var panels  = document.querySelectorAll('.wpbyem-tab-panel');

		if (!buttons.length) return;

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				var target = this.dataset.tab;

				buttons.forEach(function (btn) {
					btn.classList.remove('active');
					btn.setAttribute('aria-selected', 'false');
				});

				panels.forEach(function (panel) {
					panel.hidden = true;
				});

				this.classList.add('active');
				this.setAttribute('aria-selected', 'true');

				var panel = document.getElementById('wpbyem-tab-' + target);
				if (panel) panel.hidden = false;
			});
		});
	}
})();
