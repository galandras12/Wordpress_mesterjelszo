/**
 * Mesterjelszó - publikus jelszókérő felület viselkedése.
 * Natív (vanilla) JavaScript, nem igényel jQuery-t, mivel ez az oldal a
 * WordPress normál eszköz-betöltési láncán (wp_enqueue_script) kívül,
 * önálló HTML dokumentumként töltődik be.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initPasswordToggle();
		initForm();
	});

	/**
	 * A jelszó mező láthatóság-kapcsoló gombjának bekötése.
	 */
	function initPasswordToggle() {
		var toggle = document.getElementById('mjz-toggle-visibility');
		var input = document.getElementById('mjz-password');

		if (!toggle || !input) {
			return;
		}

		var openIcon = toggle.querySelector('.mjz-eye-open');
		var closedIcon = toggle.querySelector('.mjz-eye-closed');

		toggle.addEventListener('click', function () {
			var isVisible = input.getAttribute('type') === 'text';
			input.setAttribute('type', isVisible ? 'password' : 'text');
			toggle.setAttribute('aria-pressed', isVisible ? 'false' : 'true');

			if (openIcon && closedIcon) {
				openIcon.hidden = !isVisible ? false : true;
				closedIcon.hidden = !isVisible ? true : false;
			}

			input.focus();
		});
	}

	/**
	 * A jelszókérő űrlap AJAX-alapú beküldésének kezelése.
	 */
	function initForm() {
		var form = document.getElementById('mjz-form');
		if (!form) {
			return;
		}

		var input = document.getElementById('mjz-password');
		var button = document.getElementById('mjz-submit');
		var errorBox = document.getElementById('mjz-error');
		var spinner = document.getElementById('mjz-spinner');

		var ajaxUrl = form.getAttribute('data-ajaxurl');
		var nonce = form.getAttribute('data-nonce');
		var redirectTo = form.getAttribute('data-redirect');
		var emptyMessage = form.getAttribute('data-empty-message') || 'Kérjük, add meg a jelszót.';

		function setLoading(isLoading) {
			if (button) {
				button.disabled = isLoading;
			}
			form.setAttribute('aria-busy', isLoading ? 'true' : 'false');
			if (spinner) {
				spinner.hidden = !isLoading;
			}
		}

		function showError(message) {
			if (!errorBox) {
				return;
			}
			errorBox.textContent = message;
			errorBox.hidden = false;
			input.setAttribute('aria-invalid', 'true');
		}

		function clearError() {
			if (!errorBox) {
				return;
			}
			errorBox.textContent = '';
			errorBox.hidden = true;
			input.removeAttribute('aria-invalid');
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			clearError();

			var password = input.value;

			if (!password) {
				showError(emptyMessage);
				input.focus();
				return;
			}

			setLoading(true);

			var body = new URLSearchParams();
			body.append('action', 'mesterjelszo_verify');
			body.append('nonce', nonce);
			body.append('mesterjelszo_password', password);
			body.append('redirect_to', redirectTo);

			var rememberCheckbox = document.getElementById('mjz-remember');
			if (rememberCheckbox && rememberCheckbox.checked) {
				body.append('remember_me', '1');
			}

			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: body.toString()
			})
				.then(function (response) {
					return response
						.json()
						.catch(function () {
							return null;
						})
						.then(function (data) {
							return { ok: response.ok, data: data };
						});
				})
				.then(function (result) {
					setLoading(false);

					if (result.data && result.data.success) {
						var redirect =
							result.data.data && result.data.data.redirect
								? result.data.data.redirect
								: redirectTo;
						window.location.href = redirect;
						return;
					}

					var message =
						result.data && result.data.data && result.data.data.message
							? result.data.data.message
							: 'Hiba történt, kérjük próbáld újra.';

					showError(message);
					input.value = '';
					input.focus();
				})
				.catch(function () {
					setLoading(false);
					showError(
						'Hálózati hiba történt. Kérjük, ellenőrizd az internetkapcsolatot, majd próbáld újra.'
					);
				});
		});
	}
})();
