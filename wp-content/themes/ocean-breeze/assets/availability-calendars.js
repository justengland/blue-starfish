/**
 * Turnstile-gated Google Calendar iframe injection (client-side only).
 */
(function () {
	'use strict';

	var cfg = window.oceanBreezeAvailability || {};

	function getSections() {
		return document.querySelectorAll('.ocean-breeze-availability');
	}

	function getFrames(section) {
		return section.querySelectorAll('.ocean-breeze-availability__frame');
	}

	function createIframe(unitKey) {
		var src = cfg.calendars && cfg.calendars[unitKey];
		if (!src) {
			return null;
		}

		var labels = cfg.labels || {};
		var title = labels[unitKey] || unitKey;

		var iframe = document.createElement('iframe');
		iframe.src = src;
		iframe.title = title + ' availability';
		iframe.width = '800';
		iframe.height = '600';
		iframe.setAttribute('frameborder', '0');
		iframe.setAttribute('scrolling', 'no');
		iframe.loading = 'lazy';
		iframe.style.border = '0';
		return iframe;
	}

	function loadCalendars() {
		getSections().forEach(function (section) {
			var calendars = section.querySelector('.ocean-breeze-availability__calendars');
			if (calendars) {
				calendars.hidden = false;
			}

			getFrames(section).forEach(function (frame) {
				if (frame.dataset.loaded === '1') {
					return;
				}

				var unit = frame.closest('.ocean-breeze-availability__unit');
				var key = unit ? unit.getAttribute('data-calendar') : null;
				if (!key) {
					return;
				}

				var iframe = createIframe(key);
				if (!iframe) {
					return;
				}

				frame.appendChild(iframe);
				frame.dataset.loaded = '1';
			});
		});
	}

	function unloadCalendars() {
		getSections().forEach(function (section) {
			var calendars = section.querySelector('.ocean-breeze-availability__calendars');
			if (calendars) {
				calendars.hidden = true;
			}

			getFrames(section).forEach(function (frame) {
				frame.innerHTML = '';
				delete frame.dataset.loaded;
			});

			var prompt = section.querySelector('.ocean-breeze-availability__prompt');
			if (prompt) {
				prompt.hidden = false;
			}
		});
	}

	window.oceanBreezeAvailabilityTurnstileSuccess = function () {
		getSections().forEach(function (section) {
			var prompt = section.querySelector('.ocean-breeze-availability__prompt');
			if (prompt) {
				prompt.hidden = true;
			}
		});
		loadCalendars();
	};

	window.oceanBreezeAvailabilityTurnstileExpired = function () {
		unloadCalendars();
	};

	window.oceanBreezeAvailabilityTurnstileError = function () {
		unloadCalendars();
	};

	function initWithoutTurnstile() {
		if (!cfg.hasTurnstile) {
			loadCalendars();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initWithoutTurnstile);
	} else {
		initWithoutTurnstile();
	}
})();
