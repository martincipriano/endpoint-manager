/**
 * WPBuoy Endpoint Manager — Settings Page
 *
 * @package Wpbyem_Endpoint_Manager
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
		initImportUpload();
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

	/**
	 * The import file input is hidden — clicking the visible "Import Configuration"
	 * button opens the file picker, and choosing a file submits the form immediately.
	 */
	function initImportUpload() {
		var trigger    = document.getElementById('wpbyem-import-trigger');
		var fileInput  = document.getElementById('wpbyem-import-file');

		if (!trigger || !fileInput) return;

		trigger.addEventListener('click', function () {
			fileInput.click();
		});

		fileInput.addEventListener('change', function () {
			if (fileInput.files.length) {
				fileInput.form.submit();
			}
		});
	}
})();
