/**
 * Didban dashboard scan controller & live progress polling.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		const config = window.didbanData || null;
		const startBtn = document.getElementById('didban-start-scan-btn');
		const startBtnText = document.getElementById('didban-start-scan-text');
		const progressWrap = document.getElementById('didban-progress-wrap');
		const progressBar = document.getElementById('didban-progress-bar');
		const progressLabel = document.getElementById('didban-progress-label');
		const progressPercent = document.getElementById('didban-progress-percent');
		const errorBox = document.getElementById('didban-ajax-error');

		if (!config || !startBtn) {
			return;
		}

		let pollTimer = null;

		function showError(message) {
			if (!errorBox) {
				return;
			}
			errorBox.textContent = message || 'خطایی در ارتباط با سرور رخ داد.';
			errorBox.classList.remove('d-none');
		}

		function hideError() {
			if (errorBox) {
				errorBox.classList.add('d-none');
				errorBox.textContent = '';
			}
		}

		function updateProgressUI(data) {
			const pct = Math.min(100, Math.max(0, Number(data.progress || 0)));

			if (progressWrap) {
				progressWrap.classList.remove('d-none');
			}
			if (progressBar) {
				progressBar.style.width = pct + '%';
				progressBar.setAttribute('aria-valuenow', String(pct));
			}
			if (progressPercent) {
				progressPercent.textContent = pct + '٪';
			}
			if (progressLabel) {
				progressLabel.textContent =
					'در حال بررسی محصولات (' +
					(data.processed_products || 0) +
					' از ' +
					(data.total_products || 0) +
					')...';
			}
		}

		function setScanningState(scanning) {
			startBtn.disabled = scanning;
			if (startBtnText) {
				startBtnText.textContent = scanning ? 'در حال بررسی...' : 'شروع بررسی';
			}
		}

		async function postAjax(action) {
			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', config.nonce);

			const response = await fetch(config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			});

			return response.json();
		}

		async function pollStatus() {
			try {
				const res = await postAjax('didban_scan_status');
				if (!res || !res.success) {
					clearInterval(pollTimer);
					setScanningState(false);
					showError(res && res.data ? res.data.message : '');
					return;
				}

				const data = res.data;
				updateProgressUI(data);

				if (data.status === 'completed') {
					clearInterval(pollTimer);
					window.location.reload();
				} else if (data.status === 'failed') {
					clearInterval(pollTimer);
					setScanningState(false);
					showError(data.error_message || 'بررسی با خطا متوقف شد.');
				}
			} catch (err) {
				clearInterval(pollTimer);
				setScanningState(false);
				showError('ارتباط با سرور برقرار نشد.');
			}
		}

		function startPolling() {
			if (pollTimer) {
				clearInterval(pollTimer);
			}
			pollTimer = setInterval(pollStatus, 1500);
			pollStatus();
		}

		startBtn.addEventListener('click', async function () {
			hideError();
			setScanningState(true);

			try {
				const res = await postAjax('didban_start_scan');
				if (!res || !res.success) {
					setScanningState(false);
					showError(res && res.data ? res.data.message : '');
					return;
				}

				const data = res.data;
				updateProgressUI(data);

				if (data.status === 'completed') {
					window.location.reload();
					return;
				}

				startPolling();
			} catch (err) {
				setScanningState(false);
				showError('شروع بررسی با خطا مواجه شد.');
			}
		});

		if (config.isScanning) {
			setScanningState(true);
			startPolling();
		}
	});
})();