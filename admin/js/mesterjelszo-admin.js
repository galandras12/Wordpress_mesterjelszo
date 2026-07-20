/**
 * Mesterjelszó - admin beállítási felület viselkedése.
 * jQuery-t használ, mivel a WordPress admin felület wp-color-picker és
 * media uploader komponensei is jQuery-alapúak.
 */
(function ($) {
	'use strict';

	$(function () {
		initTabs();
		initColorPickers();
		initMediaPickers();
		initOpacitySlider();
		initBgTypeToggle();
		initPasswordToggle();
		initPasswordMatchValidation();
		initLivePreview();
	});

	/**
	 * Tabok közötti váltás kezelése, ARIA attribútumok karbantartásával.
	 */
	function initTabs() {
		var $tabs = $('.mjz-tab');
		var $panels = $('.mjz-tab-panel');

		$tabs.on('click', function () {
			var target = $(this).data('tab');

			$tabs.removeClass('is-active').attr('aria-selected', 'false');
			$(this).addClass('is-active').attr('aria-selected', 'true');

			$panels.removeClass('is-active').attr('hidden', true);
			$('#mjz-tab-' + target).addClass('is-active').removeAttr('hidden');
		});
	}

	/**
	 * A WordPress natív színválasztójának (wp-color-picker) inicializálása
	 * minden .mjz-color-picker osztályú mezőn, élő előnézet frissítéssel.
	 */
	function initColorPickers() {
		if (typeof $.fn.wpColorPicker !== 'function') {
			return;
		}

		$('.mjz-color-picker').wpColorPicker({
			change: function () {
				// A wp-color-picker késleltetve frissíti az input értékét,
				// ezért egy rövid időzítéssel biztosítjuk, hogy az
				// előnézet a friss értéket kapja.
				setTimeout(updateLivePreview, 50);
			},
			clear: function () {
				setTimeout(updateLivePreview, 50);
			}
		});
	}

	/**
	 * A WordPress média-feltöltő (wp.media) bekötése a logó és a
	 * háttérkép választó gombjaira.
	 */
	function initMediaPickers() {
		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		$('.mjz-media-select').on('click', function (event) {
			event.preventDefault();

			var target = $(this).data('target'); // pl. "mjz-logo" vagy "mjz-bgimage"
			var frame = wp.media({
				title: (window.mesterjelszoAdmin && mesterjelszoAdmin.mediaTitle) || 'Kép kiválasztása',
				button: { text: (window.mesterjelszoAdmin && mesterjelszoAdmin.mediaButton) || 'Kiválasztás' },
				multiple: false,
				library: { type: 'image' }
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var previewUrl = (attachment.sizes && attachment.sizes.thumbnail)
					? attachment.sizes.thumbnail.url
					: attachment.url;

				$('#' + target + '-id').val(attachment.id);
				$('#' + target + '-preview').html('<img src="' + escapeHtmlAttr(previewUrl) + '" alt="">');

				updateLivePreview();
			});

			frame.open();
		});

		$('.mjz-media-remove').on('click', function (event) {
			event.preventDefault();

			var target = $(this).data('target');
			var confirmMsg = (window.mesterjelszoAdmin && mesterjelszoAdmin.confirmRemove) || 'Biztosan eltávolítod a képet?';

			if (!window.confirm(confirmMsg)) {
				return;
			}

			$('#' + target + '-id').val(0);
			$('#' + target + '-preview').html('<span class="mjz-media-placeholder dashicons dashicons-format-image"></span>');

			updateLivePreview();
		});
	}

	/**
	 * Az átlátszóság csúszka aktuális értékének megjelenítése és élő
	 * előnézet frissítése.
	 */
	function initOpacitySlider() {
		var $slider = $('#mjz-bg-opacity');
		var $value = $('#mjz-bg-opacity-value');

		$slider.on('input change', function () {
			$value.text($(this).val() + '%');
			updateLivePreview();
		});
	}

	/**
	 * Háttértípus (szín / kép) váltásakor a megfelelő mezőcsoport
	 * megjelenítése / elrejtése.
	 */
	function initBgTypeToggle() {
		function refresh() {
			var isImage = $('#mjz-bg-type-image').is(':checked');
			$('#mjz-bg-color-field').toggle(!isImage);
			$('#mjz-bg-image-field').toggle(isImage);
			updateLivePreview();
		}

		$('input[name$="[bg_type]"]').on('change', refresh);
		refresh();
	}

	/**
	 * Jelszó mezők "Mutat / Elrejt" gombjainak bekötése.
	 */
	function initPasswordToggle() {
		$('.mjz-toggle-pw').on('click', function () {
			var targetId = $(this).data('target');
			var $input = $('#' + targetId);
			var isPassword = $input.attr('type') === 'password';

			$input.attr('type', isPassword ? 'text' : 'password');
			$(this).text(isPassword ? 'Elrejt' : 'Mutat');
		});
	}

	/**
	 * Kliens oldali visszajelzés arról, hogy a két jelszó-mező tartalma
	 * egyezik-e. FONTOS: ez csak felhasználói kényelmi funkció - a
	 * tényleges, biztonságkritikus egyezés-ellenőrzés mindig szerver
	 * oldalon (Mesterjelszo_Admin::sanitize_settings) történik.
	 */
	function initPasswordMatchValidation() {
		var $new = $('#mjz-password-new');
		var $confirm = $('#mjz-password-confirm');
		var $hint = $('#mjz-password-match-hint');

		function validate() {
			var newVal = $new.val();
			var confirmVal = $confirm.val();

			if (!newVal && !confirmVal) {
				$hint.text('').removeClass('mjz-hint-ok mjz-hint-error');
				return;
			}

			if (newVal.length > 0 && newVal.length < 6) {
				$hint
					.text((window.mesterjelszoAdmin && mesterjelszoAdmin.passwordTooShort) || 'A jelszónak legalább 6 karakter hosszúnak kell lennie.')
					.removeClass('mjz-hint-ok')
					.addClass('mjz-hint-error');
				return;
			}

			if (confirmVal.length === 0) {
				$hint.text('').removeClass('mjz-hint-ok mjz-hint-error');
				return;
			}

			if (newVal === confirmVal) {
				$hint
					.text('✓')
					.removeClass('mjz-hint-error')
					.addClass('mjz-hint-ok');
			} else {
				$hint
					.text((window.mesterjelszoAdmin && mesterjelszoAdmin.passwordMismatch) || 'A két jelszó nem egyezik meg.')
					.removeClass('mjz-hint-ok')
					.addClass('mjz-hint-error');
			}
		}

		$new.add($confirm).on('input', validate);
	}

	/**
	 * Az élő előnézet panel bekötése minden releváns mező változás-
	 * eseményéhez.
	 */
	function initLivePreview() {
		$('#mjz-message').on('input', updateLivePreview);
		$('#mjz-show-site-name').on('change', updateLivePreview);
		$('input[name$="[color_mode]"]').on('change', updateLivePreview);

		updateLivePreview();
	}

	/**
	 * Az előnézet panel tényleges frissítése az aktuális form-mezők
	 * értékei alapján - nem küld szerver felé kérést, tisztán kliens
	 * oldali DOM-frissítés.
	 */
	function updateLivePreview() {
		var $bg = $('#mjz-preview-bg');
		var $card = $('#mjz-preview-card');
		var $sitename = $('#mjz-preview-sitename');
		var $message = $('#mjz-preview-message');
		var $button = $('#mjz-preview-button');
		var $logo = $('#mjz-preview-logo');

		var isImage = $('#mjz-bg-type-image').is(':checked');
		var bgColor = $('#mjz-bg-color').val() || '#1a1c2c';
		var opacity = ($('#mjz-bg-opacity').val() || 100) / 100;
		var accent = $('#mjz-accent-color').val() || '#6c5ce7';
		var mode = $('input[name$="[color_mode]"]:checked').val() || 'dark';

		$bg.css('opacity', opacity);

		if (isImage) {
			var bgImgSrc = $('#mjz-bgimage-preview img').attr('src');
			if (bgImgSrc) {
				$bg.css({ 'background-image': 'url(' + bgImgSrc + ')', 'background-color': '' });
			} else {
				$bg.css({ 'background-image': 'none', 'background-color': bgColor });
			}
		} else {
			$bg.css({ 'background-image': 'none', 'background-color': bgColor });
		}

		$button.css('background-color', accent);

		if ('light' === mode) {
			$card.addClass('mjz-preview-light');
		} else {
			$card.removeClass('mjz-preview-light');
		}

		var showSiteName = $('#mjz-show-site-name').is(':checked');
		$sitename.toggle(showSiteName);

		var logoSrc = $('#mjz-logo-preview img').attr('src');
		if (logoSrc) {
			$logo.html('<img src="' + escapeHtmlAttr(logoSrc) + '" alt="">');
		} else {
			$logo.empty();
		}

		var messageText = $('#mjz-message').val() || '';
		$message.text(messageText.replace(/<[^>]*>/g, ''));
	}

	/**
	 * Egyszerű segédfüggvény HTML attribútumba kerülő értékek biztonságos
	 * kiírásához (kizárólag a saját, kliens oldali DOM-manipulációnkhoz -
	 * nem helyettesíti a szerver oldali escapelést).
	 *
	 * @param {string} value
	 * @return {string}
	 */
	function escapeHtmlAttr(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}
})(jQuery);
