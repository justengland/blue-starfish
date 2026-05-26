/**
 * Contact form AJAX + Turnstile UI (must load as external file; inline scripts break via wpautop).
 */
(function () {
	'use strict';

	var cfg = window.oceanBreezeContactForm || {};

	function oceanBreezeSetTurnstileComplete(complete) {
		var submit = document.querySelector('.ocean-breeze-contact-form__submit');
		if (!submit || !cfg.hasTurnstile) {
			return;
		}
		submit.disabled = !complete;
		submit.setAttribute('aria-disabled', complete ? 'false' : 'true');
	}

	function oceanBreezeTurnstileReady() {
		var form = document.getElementById('contact-form');
		if (!form || !cfg.hasTurnstile) {
			return true;
		}
		var input = form.querySelector('input[name="cf-turnstile-response"]');
		return Boolean(input && input.value);
	}

	window.oceanBreezeTurnstileSuccess = function () {
		oceanBreezeSetTurnstileComplete(true);
	};
	window.oceanBreezeTurnstileExpired = function () {
		oceanBreezeSetTurnstileComplete(false);
	};
	window.oceanBreezeTurnstileError = function () {
		oceanBreezeSetTurnstileComplete(false);
	};

	/** Fallback if Turnstile callback does not fire but token is present. */
	function oceanBreezeWatchTurnstileToken() {
		var form = document.getElementById('contact-form');
		if (!form || !document.querySelector('.ocean-breeze-contact-form.has-turnstile')) {
			return;
		}

		function syncFromToken() {
			var input = form.querySelector('input[name="cf-turnstile-response"]');
			oceanBreezeSetTurnstileComplete(Boolean(input && input.value));
		}

		var observer = new MutationObserver(syncFromToken);
		observer.observe(form, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['value'],
		});
		form.addEventListener('input', syncFromToken, true);
		form.addEventListener('change', syncFromToken, true);
		syncFromToken();
	}

	function initContactForm() {
		var form = document.getElementById('contact-form');
		if (!form) {
			return;
		}

		oceanBreezeWatchTurnstileToken();

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			if (!oceanBreezeTurnstileReady()) {
				return;
			}

			var submitButton = form.querySelector('button[type="submit"]');
			var errorDiv = document.getElementById('contact-form-error');
			var originalText = submitButton.textContent.trim();

			submitButton.disabled = true;
			submitButton.textContent = cfg.sending || 'Sending...';
			if (errorDiv) {
				errorDiv.style.display = 'none';
			}

			var formData = new FormData(form);
			formData.append('ob_contact_submit', '1');

			fetch(form.action, {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					if (cfg.hasTurnstile) {
						submitButton.disabled = true;
					} else {
						submitButton.disabled = false;
					}
					submitButton.textContent = originalText;

					if (data.success) {
						var modal = document.getElementById('contact-success-modal');
						var messageEl = document.getElementById('contact-success-message');

						if (modal && messageEl) {
							messageEl.textContent = data.data;
							modal.showModal();

							var closeBtn = modal.querySelector('.ocean-breeze-contact-modal__close');
							if (closeBtn) {
								closeBtn.addEventListener('click', function () {
									modal.close();
									window.location.href = cfg.homeUrl || '/';
								});
							}

							modal.addEventListener('click', function (e) {
								var dialogDimensions = modal.getBoundingClientRect();
								if (
									e.clientX < dialogDimensions.left ||
									e.clientX > dialogDimensions.right ||
									e.clientY < dialogDimensions.top ||
									e.clientY > dialogDimensions.bottom
								) {
									modal.close();
									window.location.href = cfg.homeUrl || '/';
								}
							});
						} else {
							alert(data.data);
							window.location.href = cfg.homeUrl || '/';
						}
					} else {
						if (errorDiv) {
							errorDiv.innerHTML = '<p>' + (data.data || 'Error submitting form.') + '</p>';
							errorDiv.style.display = 'block';
						} else {
							alert(data.data || 'Error submitting form.');
						}

						if (typeof turnstile !== 'undefined') {
							turnstile.reset();
						}
						oceanBreezeTurnstileExpired();
					}
				})
				.catch(function () {
					if (cfg.hasTurnstile) {
						submitButton.disabled = true;
					} else {
						submitButton.disabled = false;
					}
					submitButton.textContent = originalText;
					if (errorDiv) {
						errorDiv.innerHTML =
							'<p>' + (cfg.networkError || 'A network error occurred. Please try again.') + '</p>';
						errorDiv.style.display = 'block';
					} else {
						alert(cfg.networkError || 'A network error occurred. Please try again.');
					}

					if (typeof turnstile !== 'undefined') {
						turnstile.reset();
					}
					oceanBreezeTurnstileExpired();
				});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initContactForm);
	} else {
		initContactForm();
	}
})();
