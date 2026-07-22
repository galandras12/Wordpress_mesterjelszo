<?php
/**
 * Az admin beállítási oldal HTML kimenete.
 *
 * Ezt a fájlt a Mesterjelszo_Admin::render_settings_page() metódus hívja
 * meg, és a következő változókat bocsátja rendelkezésre:
 *
 * @package Mesterjelszo
 * @var array $settings     A mentett (vagy alapértelmezett) beállítások tömbje.
 * @var bool  $has_password Van-e már beállítva mesterjelszó.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mjz-admin-wrap">

	<div class="mjz-admin-header">
		<div class="mjz-admin-header-icon" aria-hidden="true">
			<span class="dashicons dashicons-lock"></span>
		</div>
		<div>
			<h1><?php esc_html_e( 'Mesterjelszó', 'mesterjelszo' ); ?></h1>
			<p class="mjz-admin-subtitle">
				<?php esc_html_e( 'Védd egyetlen jelszóval a teljes weboldalt: oldalak, bejegyzések, egyedi tartalomtípusok, a REST API és a bejelentkezési felület.', 'mesterjelszo' ); ?>
			</p>
		</div>

		<div class="mjz-status-badge <?php echo ( ! empty( $settings['enabled'] ) && $has_password ) ? 'mjz-status-active' : 'mjz-status-inactive'; ?>">
			<?php if ( ! empty( $settings['enabled'] ) && $has_password ) : ?>
				<span class="mjz-status-dot"></span>
				<?php esc_html_e( 'Aktív védelem', 'mesterjelszo' ); ?>
			<?php elseif ( ! empty( $settings['enabled'] ) && ! $has_password ) : ?>
				<span class="mjz-status-dot"></span>
				<?php esc_html_e( 'Nincs jelszó beállítva', 'mesterjelszo' ); ?>
			<?php else : ?>
				<span class="mjz-status-dot"></span>
				<?php esc_html_e( 'Kikapcsolva', 'mesterjelszo' ); ?>
			<?php endif; ?>
		</div>
	</div>

	<?php settings_errors( 'mesterjelszo_settings_group' ); ?>

	<div class="mjz-layout">

		<div class="mjz-main-column">

			<nav class="mjz-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Beállítási kategóriák', 'mesterjelszo' ); ?>">
				<button type="button" class="mjz-tab is-active" data-tab="general" role="tab" aria-selected="true" id="mjz-tab-btn-general" aria-controls="mjz-tab-general">
					<span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'Alapbeállítások', 'mesterjelszo' ); ?>
				</button>
				<button type="button" class="mjz-tab" data-tab="appearance" role="tab" aria-selected="false" id="mjz-tab-btn-appearance" aria-controls="mjz-tab-appearance">
					<span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Megjelenés', 'mesterjelszo' ); ?>
				</button>
				<button type="button" class="mjz-tab" data-tab="security" role="tab" aria-selected="false" id="mjz-tab-btn-security" aria-controls="mjz-tab-security">
					<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Biztonság', 'mesterjelszo' ); ?>
				</button>
				<button type="button" class="mjz-tab" data-tab="log" role="tab" aria-selected="false" id="mjz-tab-btn-log" aria-controls="mjz-tab-log">
					<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Bejelentkezési napló', 'mesterjelszo' ); ?>
				</button>
			</nav>

			<script>
			/*
			 * Natív (vanilla) JavaScript tab-váltó, SZÁNDÉKOSAN nem jQuery-n
			 * keresztül, és SZÁNDÉKOSAN nem egy külső, betöltési sorrendtől
			 * függő fájlban. Ennek oka egy 1.0.3-ban javított hiba: ha egy
			 * másik, ugyanazon az admin oldalon aktív bővítmény szkriptje
			 * hibát dob a jQuery(document).ready() eseménysorban, az
			 * megakaszthatja az utána regisztrált ready callback-eket
			 * (beleértve a Mesterjelszó admin.js fájlját is) - emiatt a
			 * fülek hover állapota (tiszta CSS, mindig működik) látszott,
			 * de a kattintásra történő váltás (a korábbi, jQuery-függő
			 * megoldás) nem. Ez a beágyazott, közvetlenül a nav mellett
			 * futó script semmilyen más szkript betöltésétől vagy
			 * hibájától nem függ, ezért garantáltan mindig működik.
			 */
			(function () {
				'use strict';

				function mjzInitTabs() {
					var tabs = document.querySelectorAll('.mjz-tab');
					var panels = document.querySelectorAll('.mjz-tab-panel');

					if (!tabs.length) {
						return;
					}

					for (var i = 0; i < tabs.length; i++) {
						tabs[i].addEventListener('click', function (event) {
							var target = event.currentTarget.getAttribute('data-tab');

							for (var j = 0; j < tabs.length; j++) {
								tabs[j].classList.remove('is-active');
								tabs[j].setAttribute('aria-selected', 'false');
							}
							event.currentTarget.classList.add('is-active');
							event.currentTarget.setAttribute('aria-selected', 'true');

							for (var k = 0; k < panels.length; k++) {
								panels[k].classList.remove('is-active');
								panels[k].setAttribute('hidden', 'hidden');
							}

							var targetPanel = document.getElementById('mjz-tab-' + target);
							if (targetPanel) {
								targetPanel.classList.add('is-active');
								targetPanel.removeAttribute('hidden');
							}
						});
					}
				}

				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', mjzInitTabs);
				} else {
					mjzInitTabs();
				}
			})();
			</script>

			<!--
				FONTOS: az alábbi <form> KIZÁRÓLAG a tényleges beállítás-mezőket
				(Alapbeállítások / Megjelenés / Biztonság fülek) tartalmazza. A
				Napló fülnek szándékosan saját, ezen a formon KÍVÜLI form-ja van
				(lásd lentebb) - egymásba ágyazott <form> elemek érvénytelen
				HTML-t eredményeznének, és megtörnék mind a mentést, mind a
				napló törlését.
			-->
			<form method="post" action="options.php" id="mjz-settings-form" novalidate>
				<?php settings_fields( 'mesterjelszo_settings_group' ); ?>

				<!-- ===================== ALAPBEÁLLÍTÁSOK TAB ===================== -->
				<section class="mjz-tab-panel is-active" id="mjz-tab-general" role="tabpanel" aria-labelledby="mjz-tab-btn-general">

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Védelem állapota', 'mesterjelszo' ); ?></h2>

						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( 'Weboldal védelmének bekapcsolása', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'Ha ki van kapcsolva, a weboldal a szokásos módon, jelszó nélkül elérhető mindenki számára.', 'mesterjelszo' ); ?></span>
							</span>
						</label>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Mesterjelszó', 'mesterjelszo' ); ?></h2>
						<p class="mjz-field-description">
							<?php if ( $has_password ) : ?>
								<span class="mjz-inline-status mjz-inline-status-ok">✓ <?php esc_html_e( 'Jelenleg van beállítva mesterjelszó.', 'mesterjelszo' ); ?></span>
								<?php esc_html_e( 'Az alábbi mezők kitöltésével lecserélheted. Biztonsági okokból a jelenlegi jelszó nem jeleníthető meg.', 'mesterjelszo' ); ?>
							<?php else : ?>
								<span class="mjz-inline-status mjz-inline-status-warn">⚠ <?php esc_html_e( 'Még nincs mesterjelszó beállítva - a weboldal jelenleg nem védett, még ha a fenti kapcsoló be is van kapcsolva.', 'mesterjelszo' ); ?></span>
							<?php endif; ?>
						</p>

						<div class="mjz-field-grid">
							<div class="mjz-field">
								<label for="mjz-password-new"><?php esc_html_e( 'Új mesterjelszó', 'mesterjelszo' ); ?></label>
								<div class="mjz-password-input-row">
									<input type="password" id="mjz-password-new" name="mesterjelszo_master_password" autocomplete="new-password" placeholder="<?php echo $has_password ? esc_attr__( '••••••••••• (hagyd üresen, ha nem módosítod)', 'mesterjelszo' ) : esc_attr__( 'Add meg az új mesterjelszót', 'mesterjelszo' ); ?>">
									<button type="button" class="button mjz-toggle-pw" data-target="mjz-password-new"><?php esc_html_e( 'Mutat', 'mesterjelszo' ); ?></button>
								</div>
							</div>
							<div class="mjz-field">
								<label for="mjz-password-confirm"><?php esc_html_e( 'Új mesterjelszó megerősítése', 'mesterjelszo' ); ?></label>
								<div class="mjz-password-input-row">
									<input type="password" id="mjz-password-confirm" name="mesterjelszo_master_password_confirm" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Ismételd meg a jelszót', 'mesterjelszo' ); ?>">
									<button type="button" class="button mjz-toggle-pw" data-target="mjz-password-confirm"><?php esc_html_e( 'Mutat', 'mesterjelszo' ); ?></button>
								</div>
							</div>
						</div>
						<p class="mjz-field-hint" id="mjz-password-match-hint" aria-live="polite"></p>

						<?php if ( $has_password ) : ?>
							<div class="mjz-field mjz-current-password-row" id="mjz-current-password-row">
								<label for="mjz-current-password-display"><?php esc_html_e( 'Jelenlegi mesterjelszó', 'mesterjelszo' ); ?></label>
								<div class="mjz-password-input-row">
									<input type="text" id="mjz-current-password-display" class="mjz-current-password-display" value="••••••••••••" readonly>
									<button type="button" class="button" id="mjz-reveal-password" data-nonce="<?php echo esc_attr( wp_create_nonce( 'mesterjelszo_reveal_password' ) ); ?>"><?php esc_html_e( 'Megtekintés', 'mesterjelszo' ); ?></button>
									<button type="button" class="button-link" id="mjz-copy-password" hidden><?php esc_html_e( 'Másolás', 'mesterjelszo' ); ?></button>
								</div>
								<p class="mjz-field-description"><?php esc_html_e( 'Ez segít más adminisztrátoroknak nyomon követni az aktuálisan beállított mesterjelszót, anélkül hogy azt újra be kellene állítaniuk. A megtekintés minden alkalommal adminisztrátori jogosultságot igényel.', 'mesterjelszo' ); ?></p>
							</div>
						<?php endif; ?>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Adminisztrátorok', 'mesterjelszo' ); ?></h2>
						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bypass_admins]" value="1" <?php checked( ! empty( $settings['bypass_admins'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( 'Bejelentkezett adminisztrátorok mentesítése', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'Ha be van kapcsolva, a már bejelentkezett, adminisztrátori jogosultsággal rendelkező felhasználók a mesterjelszó megadása nélkül is látják a weboldalt. A wp-admin irányítópult adminisztrátorok számára biztonsági okból mindig elérhető marad, ezt a kapcsolót függetlenül a kizárás elkerülése érdekében.', 'mesterjelszo' ); ?></span>
							</span>
						</label>
					</div>

				</section>

				<!-- ===================== MEGJELENÉS TAB ===================== -->
				<section class="mjz-tab-panel" id="mjz-tab-appearance" role="tabpanel" aria-labelledby="mjz-tab-btn-appearance" hidden>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Logó', 'mesterjelszo' ); ?></h2>
						<div class="mjz-media-picker" id="mjz-logo-picker">
							<div class="mjz-media-preview" id="mjz-logo-preview">
								<?php if ( ! empty( $settings['logo_id'] ) && wp_get_attachment_image_url( (int) $settings['logo_id'], 'thumbnail' ) ) : ?>
									<img src="<?php echo esc_url( wp_get_attachment_image_url( (int) $settings['logo_id'], 'thumbnail' ) ); ?>" alt="">
								<?php else : ?>
									<span class="mjz-media-placeholder dashicons dashicons-format-image"></span>
								<?php endif; ?>
							</div>
							<div class="mjz-media-actions">
								<input type="hidden" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[logo_id]" id="mjz-logo-id" value="<?php echo esc_attr( $settings['logo_id'] ); ?>">
								<button type="button" class="button button-secondary mjz-media-select" data-target="mjz-logo"><?php esc_html_e( 'Logó kiválasztása', 'mesterjelszo' ); ?></button>
								<button type="button" class="button-link mjz-media-remove" data-target="mjz-logo"><?php esc_html_e( 'Eltávolítás', 'mesterjelszo' ); ?></button>
							</div>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Weboldal neve és üzenet', 'mesterjelszo' ); ?></h2>

						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[show_site_name]" value="1" id="mjz-show-site-name" <?php checked( ! empty( $settings['show_site_name'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( 'Weboldal nevének megjelenítése', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'A weboldal nevét (Beállítások → Általános) automatikusan megjeleníti a jelszókérő felület tetején.', 'mesterjelszo' ); ?></span>
							</span>
						</label>

						<div class="mjz-field" style="margin-top:18px;">
							<label for="mjz-message"><?php esc_html_e( 'Tájékoztató üzenet a látogatóknak', 'mesterjelszo' ); ?></label>
							<textarea name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[message]" id="mjz-message" rows="4" class="large-text"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
							<p class="mjz-field-description"><?php esc_html_e( 'Korlátozott HTML használható (pl. <strong>, <em>, <a>). Ez a szöveg jelenik meg a jelszómező felett.', 'mesterjelszo' ); ?></p>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Háttér', 'mesterjelszo' ); ?></h2>

						<div class="mjz-field">
							<span class="mjz-field-label-static"><?php esc_html_e( 'Háttér típusa', 'mesterjelszo' ); ?></span>
							<div class="mjz-radio-group">
								<label class="mjz-radio-pill">
									<input type="radio" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bg_type]" value="color" id="mjz-bg-type-color" <?php checked( 'color', $settings['bg_type'] ); ?>>
									<?php esc_html_e( 'Egyszínű háttér', 'mesterjelszo' ); ?>
								</label>
								<label class="mjz-radio-pill">
									<input type="radio" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bg_type]" value="image" id="mjz-bg-type-image" <?php checked( 'image', $settings['bg_type'] ); ?>>
									<?php esc_html_e( 'Háttérkép', 'mesterjelszo' ); ?>
								</label>
							</div>
						</div>

						<div class="mjz-field-grid">
							<div class="mjz-field" id="mjz-bg-color-field">
								<label for="mjz-bg-color"><?php esc_html_e( 'Háttérszín', 'mesterjelszo' ); ?></label>
								<input type="text" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bg_color]" id="mjz-bg-color" class="mjz-color-picker" value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
							</div>

							<div class="mjz-field" id="mjz-bg-image-field">
								<span class="mjz-field-label-static"><?php esc_html_e( 'Háttérkép', 'mesterjelszo' ); ?></span>
								<div class="mjz-media-picker" id="mjz-bgimage-picker">
									<div class="mjz-media-preview mjz-media-preview-wide" id="mjz-bgimage-preview">
										<?php if ( ! empty( $settings['bg_image_id'] ) && wp_get_attachment_image_url( (int) $settings['bg_image_id'], 'medium' ) ) : ?>
											<img src="<?php echo esc_url( wp_get_attachment_image_url( (int) $settings['bg_image_id'], 'medium' ) ); ?>" alt="">
										<?php else : ?>
											<span class="mjz-media-placeholder dashicons dashicons-format-image"></span>
										<?php endif; ?>
									</div>
									<div class="mjz-media-actions">
										<input type="hidden" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bg_image_id]" id="mjz-bgimage-id" value="<?php echo esc_attr( $settings['bg_image_id'] ); ?>">
										<button type="button" class="button button-secondary mjz-media-select" data-target="mjz-bgimage"><?php esc_html_e( 'Kép kiválasztása', 'mesterjelszo' ); ?></button>
										<button type="button" class="button-link mjz-media-remove" data-target="mjz-bgimage"><?php esc_html_e( 'Eltávolítás', 'mesterjelszo' ); ?></button>
									</div>
								</div>
							</div>
						</div>

						<div class="mjz-field">
							<label for="mjz-bg-opacity"><?php esc_html_e( 'Háttér átlátszósága', 'mesterjelszo' ); ?> <span id="mjz-bg-opacity-value" class="mjz-range-value"><?php echo esc_html( $settings['bg_opacity'] ); ?>%</span></label>
							<input type="range" min="0" max="100" step="1" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[bg_opacity]" id="mjz-bg-opacity" value="<?php echo esc_attr( $settings['bg_opacity'] ); ?>">
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Színek és megjelenési mód', 'mesterjelszo' ); ?></h2>

						<div class="mjz-field">
							<span class="mjz-field-label-static"><?php esc_html_e( 'Megjelenési mód', 'mesterjelszo' ); ?></span>
							<div class="mjz-radio-group">
								<label class="mjz-radio-pill">
									<input type="radio" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[color_mode]" value="light" id="mjz-mode-light" <?php checked( 'light', $settings['color_mode'] ); ?>>
									<?php esc_html_e( 'Világos', 'mesterjelszo' ); ?>
								</label>
								<label class="mjz-radio-pill">
									<input type="radio" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[color_mode]" value="dark" id="mjz-mode-dark" <?php checked( 'dark', $settings['color_mode'] ); ?>>
									<?php esc_html_e( 'Sötét', 'mesterjelszo' ); ?>
								</label>
								<label class="mjz-radio-pill">
									<input type="radio" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[color_mode]" value="auto" id="mjz-mode-auto" <?php checked( 'auto', $settings['color_mode'] ); ?>>
									<?php esc_html_e( 'Automatikus (rendszer szerint)', 'mesterjelszo' ); ?>
								</label>
							</div>
						</div>

						<div class="mjz-field-grid">
							<div class="mjz-field">
								<label for="mjz-accent-color"><?php esc_html_e( 'Kiemelő szín (gomb, ikon)', 'mesterjelszo' ); ?></label>
								<input type="text" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[accent_color]" id="mjz-accent-color" class="mjz-color-picker" value="<?php echo esc_attr( $settings['accent_color'] ); ?>">
							</div>
							<div class="mjz-field">
								<label for="mjz-text-color"><?php esc_html_e( 'Szöveg színe', 'mesterjelszo' ); ?></label>
								<input type="text" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[text_color]" id="mjz-text-color" class="mjz-color-picker" value="<?php echo esc_attr( $settings['text_color'] ); ?>">
							</div>
						</div>
					</div>

				</section>

				<!-- ===================== BIZTONSÁG TAB ===================== -->
				<section class="mjz-tab-panel" id="mjz-tab-security" role="tabpanel" aria-labelledby="mjz-tab-btn-security" hidden>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Munkamenet', 'mesterjelszo' ); ?></h2>
						<div class="mjz-field">
							<label for="mjz-session-duration"><?php esc_html_e( 'Munkamenet hossza (óra)', 'mesterjelszo' ); ?></label>
							<input type="number" min="1" max="720" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[session_duration]" id="mjz-session-duration" value="<?php echo esc_attr( $settings['session_duration'] ); ?>" class="small-text">
							<p class="mjz-field-description"><?php esc_html_e( 'Ennyi óráig marad feloldva a weboldal egy látogató böngészőjében a sikeres jelszó megadása után - kivéve, ha a látogató az alábbi "Emlékezz rám" opciót választja.', 'mesterjelszo' ); ?></p>
						</div>

						<hr class="mjz-field-divider">

						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[remember_me_enabled]" value="1" id="mjz-remember-me-enabled" <?php checked( ! empty( $settings['remember_me_enabled'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( '"Emlékezz rám" opció engedélyezése a látogatóknak', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'Ha be van kapcsolva, a jelszókérő felületen megjelenik egy jelölőnégyzet, amellyel a látogató kérheti, hogy a böngészője hosszabb ideig maradjon feloldva. Alapértelmezetten KI van kapcsolva, ilyenkor mindig a fenti munkamenet-hossz érvényes, jelölőnégyzet nélkül.', 'mesterjelszo' ); ?></span>
							</span>
						</label>

						<div class="mjz-field" id="mjz-remember-me-days-field" style="margin-top:16px;">
							<label for="mjz-remember-me-days"><?php esc_html_e( '"Emlékezz rám" munkamenet hossza (nap)', 'mesterjelszo' ); ?></label>
							<input type="number" min="1" max="365" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[remember_me_days]" id="mjz-remember-me-days" value="<?php echo esc_attr( $settings['remember_me_days'] ); ?>" class="small-text">
							<p class="mjz-field-description"><?php esc_html_e( 'Ennyi napig marad feloldva a böngésző, ha a látogató bejelöli az "Emlékezz rám" opciót. Alapértelmezés: 15 nap.', 'mesterjelszo' ); ?></p>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Brute-force védelem', 'mesterjelszo' ); ?></h2>
						<div class="mjz-field-grid">
							<div class="mjz-field">
								<label for="mjz-max-attempts"><?php esc_html_e( 'Sikertelen próbálkozások limitje', 'mesterjelszo' ); ?></label>
								<input type="number" min="1" max="50" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[max_attempts]" id="mjz-max-attempts" value="<?php echo esc_attr( $settings['max_attempts'] ); ?>" class="small-text">
							</div>
							<div class="mjz-field">
								<label for="mjz-lockout-duration"><?php esc_html_e( 'Zárolás időtartama (perc)', 'mesterjelszo' ); ?></label>
								<input type="number" min="1" max="1440" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[lockout_duration]" id="mjz-lockout-duration" value="<?php echo esc_attr( $settings['lockout_duration'] ); ?>" class="small-text">
							</div>
						</div>
						<p class="mjz-field-description"><?php esc_html_e( 'A megadott számú sikertelen próbálkozás után az adott látogató IP-címéhez tartozó munkamenet ideiglenesen zárolásra kerül. Az IP-cím soha nem kerül olvasható formában tárolásra, kizárólag egy anonimizált hash formájában.', 'mesterjelszo' ); ?></p>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'REST API kivételek (Jetpack és hasonló szolgáltatások)', 'mesterjelszo' ); ?></h2>
						<div class="mjz-field">
							<label for="mjz-rest-exceptions"><?php esc_html_e( 'Kivételezett REST API route-ok (soronként egy)', 'mesterjelszo' ); ?></label>
							<textarea name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[rest_api_exceptions]" id="mjz-rest-exceptions" rows="4" class="large-text code"><?php echo esc_textarea( $settings['rest_api_exceptions'] ); ?></textarea>
							<p class="mjz-field-description">
								<?php esc_html_e( 'Az itt felsorolt REST API route-prefixek mindig elérhetők maradnak, függetlenül a jelszóvédelemtől - ezekre a saját, aláírás- vagy token-alapú hitelesítésüket használó szolgáltatásoknak (pl. Jetpack) van szükségük. Enélkül a szolgáltatás "a weboldal nem elérhető" hibát adhat vissza. Az xmlrpc.php végpont biztonsági okból mindig automatikusan mentesül.', 'mesterjelszo' ); ?>
							</p>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Speciális / kompatibilitás', 'mesterjelszo' ); ?></h2>

						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[use_503_status]" value="1" <?php checked( ! empty( $settings['use_503_status'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( '503-as HTTP állapotkód küldése a jelszókérő oldalon', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'Alapértelmezetten KIKAPCSOLVA. Egyes tárhelyszolgáltatók, CDN-ek vagy biztonsági bővítmények (pl. Wordfence, LiteSpeed Cache) a saját, márkázott hibaoldalukkal helyettesíthetik az 503-as választ, aminek következtében a teljes weboldal - a bejelentkezési felülettel együtt - teljesen elérhetetlenné válhat a jelszókérő felület helyett. Csak akkor kapcsold be, ha megbizonyosodtál róla, hogy a tárhelyed ezt helyesen kezeli.', 'mesterjelszo' ); ?></span>
							</span>
						</label>

						<div class="mjz-field" style="margin-top:18px;">
							<label for="mjz-ajax-exceptions"><?php esc_html_e( 'AJAX végpont kivételek (soronként egy action név)', 'mesterjelszo' ); ?></label>
							<textarea name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[ajax_action_exceptions]" id="mjz-ajax-exceptions" rows="3" class="large-text code"><?php echo esc_textarea( $settings['ajax_action_exceptions'] ); ?></textarea>
							<p class="mjz-field-description"><?php esc_html_e( 'Ha egy másik bővítmény (pl. nagy fájlfeltöltő) saját admin-ajax.php végpontot használ a látogatói oldalon, és zárolt állapotban nem működik, add hozzá a bővítmény AJAX action nevét ehhez a listához. Az action nevet a bővítmény dokumentációjában, vagy a böngésző fejlesztői eszközeinek Hálózat fülén találhatod meg (a kérés "action" paramétere).', 'mesterjelszo' ); ?></p>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Megbízható IP-címek', 'mesterjelszo' ); ?></h2>

						<label class="mjz-switch-row">
							<span class="mjz-switch">
								<input type="checkbox" name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[trusted_ips_enabled]" value="1" id="mjz-trusted-ips-enabled" <?php checked( ! empty( $settings['trusted_ips_enabled'] ) ); ?>>
								<span class="mjz-switch-slider" aria-hidden="true"></span>
							</span>
							<span class="mjz-switch-label">
								<strong><?php esc_html_e( 'Megbízható IP-címek engedélyezése', 'mesterjelszo' ); ?></strong>
								<span class="mjz-field-description"><?php esc_html_e( 'Ha be van kapcsolva, az alábbi listán szereplő IP-címekről érkező látogatók a mesterjelszó megadása nélkül, megbízható felhasználóként léphetnek tovább.', 'mesterjelszo' ); ?></span>
							</span>
						</label>

						<div class="mjz-field" id="mjz-trusted-ips-field" style="margin-top:16px;">
							<label for="mjz-trusted-ips"><?php esc_html_e( 'IP-címek listája (soronként egy, CIDR jelölés is támogatott, pl. 203.0.113.0/24)', 'mesterjelszo' ); ?></label>
							<textarea name="<?php echo esc_attr( MESTERJELSZO_OPTION_KEY ); ?>[trusted_ips]" id="mjz-trusted-ips" rows="4" class="large-text code"><?php echo esc_textarea( $settings['trusted_ips'] ); ?></textarea>
						</div>
					</div>

					<div class="mjz-card-box mjz-info-box">
						<h2><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Tudnivalók', 'mesterjelszo' ); ?></h2>
						<ul>
							<li><?php esc_html_e( 'A mesterjelszó egyirányú, a WordPress core jelszó-kezelésével megegyező hash formában kerül tárolásra, ez alapján történik a látogatók hitelesítése. A "Jelenlegi mesterjelszó megtekintése" funkcióhoz emellett egy titkosított, visszafejthető másolat is tárolásra kerül, amely kizárólag admin jogosultsággal kérdezhető le.', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'A védelem kiterjed az oldalakra, bejegyzésekre, egyedi tartalomtípusokra, a bejelentkezési felületre és a WordPress REST API-ra (a fent megadott kivételek kivételével).', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'Az xmlrpc.php végpont mindig kihagyásra kerül a zárolásból, hogy a rá támaszkodó szolgáltatások (pl. Jetpack, remote publishing eszközök) továbbra is működjenek.', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'A jelszókérő felület alapértelmezetten sima HTTP 200-as válaszkóddal jelenik meg (a keresőmotoros indexelést a noindex jelölés önmagában megakadályozza), hogy elkerüljük a tárhelyi/CDN-szintű hibaoldal-helyettesítést. A 503-as állapotkód opcionálisan bekapcsolható fent.', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'A közvetlenül, webszerver szinten kiszolgált statikus fájlok (pl. a feltöltési mappában lévő képek közvetlen linkje) védelméhez a webszerver (Apache/Nginx) szintű beállítás is szükséges lehet, mivel ezeket a WordPress alapból nem PHP-n keresztül szolgálja ki.', 'mesterjelszo' ); ?></li>
						</ul>
					</div>

				</section>

				<p class="submit mjz-submit-row">
					<?php submit_button( __( 'Beállítások mentése', 'mesterjelszo' ), 'primary mjz-save-button', 'submit', false ); ?>
				</p>
			</form>

			<!-- ===================== NAPLÓ TAB ===================== -->
			<?php
			$mjz_log_counts = class_exists( 'Mesterjelszo_Login_Log' )
				? Mesterjelszo_Login_Log::get_counts()
				: array(
					'day'   => 0,
					'week'  => 0,
					'month' => 0,
					'year'  => 0,
				);
			$mjz_log_entries = class_exists( 'Mesterjelszo_Login_Log' ) ? Mesterjelszo_Login_Log::get_recent_entries( 100 ) : array();
			?>
			<section class="mjz-tab-panel" id="mjz-tab-log" role="tabpanel" aria-labelledby="mjz-tab-btn-log" hidden>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Sikertelen bejelentkezési próbálkozások', 'mesterjelszo' ); ?></h2>
						<div class="mjz-stat-grid">
							<div class="mjz-stat-box">
								<span class="mjz-stat-value"><?php echo esc_html( $mjz_log_counts['day'] ); ?></span>
								<span class="mjz-stat-label"><?php esc_html_e( '1 napon belül', 'mesterjelszo' ); ?></span>
							</div>
							<div class="mjz-stat-box">
								<span class="mjz-stat-value"><?php echo esc_html( $mjz_log_counts['week'] ); ?></span>
								<span class="mjz-stat-label"><?php esc_html_e( '1 héten belül', 'mesterjelszo' ); ?></span>
							</div>
							<div class="mjz-stat-box">
								<span class="mjz-stat-value"><?php echo esc_html( $mjz_log_counts['month'] ); ?></span>
								<span class="mjz-stat-label"><?php esc_html_e( '1 hónapon belül', 'mesterjelszo' ); ?></span>
							</div>
							<div class="mjz-stat-box">
								<span class="mjz-stat-value"><?php echo esc_html( $mjz_log_counts['year'] ); ?></span>
								<span class="mjz-stat-label"><?php esc_html_e( '1 éven belül', 'mesterjelszo' ); ?></span>
							</div>
						</div>
					</div>

					<div class="mjz-card-box">
						<h2><?php esc_html_e( 'Legutóbbi próbálkozások', 'mesterjelszo' ); ?></h2>

						<?php if ( empty( $mjz_log_entries ) ) : ?>
							<p class="mjz-field-description"><?php esc_html_e( 'Még nem történt sikertelen bejelentkezési próbálkozás.', 'mesterjelszo' ); ?></p>
						<?php else : ?>
							<div class="mjz-log-table-wrap">
								<table class="mjz-log-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Időpont', 'mesterjelszo' ); ?></th>
											<th><?php esc_html_e( 'IP-cím', 'mesterjelszo' ); ?></th>
											<th><?php esc_html_e( 'Ország', 'mesterjelszo' ); ?></th>
											<th><?php esc_html_e( 'Város', 'mesterjelszo' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $mjz_log_entries as $mjz_entry ) : ?>
											<tr>
												<td><?php echo esc_html( mysql2date( 'Y.m.d. H:i', $mjz_entry->created_at ) ); ?></td>
												<td><?php echo esc_html( $mjz_entry->ip_address ); ?></td>
												<td><?php echo esc_html( $mjz_entry->country ? $mjz_entry->country : __( 'Feldolgozás alatt…', 'mesterjelszo' ) ); ?></td>
												<td><?php echo esc_html( $mjz_entry->city ? $mjz_entry->city : '—' ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>

						<p class="mjz-field-description"><?php esc_html_e( 'A geolokációs adatok (ország, város) egy külső, ingyenes IP-lekérdező szolgáltatáson keresztül, késleltetve töltődnek fel, és 30 napig gyorsítótárazva vannak. A napló legfeljebb 1 évig őrzi meg az adatokat, utána automatikusan törlődnek.', 'mesterjelszo' ); ?></p>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mesterjelszo' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Biztosan törlöd a teljes bejelentkezési naplót? Ez nem vonható vissza.', 'mesterjelszo' ) ); ?>');">
							<?php wp_nonce_field( 'mesterjelszo_clear_log_action', 'mesterjelszo_clear_log_nonce' ); ?>
							<button type="submit" name="mesterjelszo_clear_log" value="1" class="button button-secondary"><?php esc_html_e( 'Napló törlése', 'mesterjelszo' ); ?></button>
						</form>
					</div>

					<div class="mjz-card-box mjz-info-box">
						<h2><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Adatvédelmi tudnivaló', 'mesterjelszo' ); ?></h2>
						<p><?php esc_html_e( 'Ez a napló - a plugin más részeitől eltérően - a látogatók valódi IP-címét tárolja, kifejezetten biztonsági célból (támadási minták nyomon követése). Ha ezt a funkciót használod, érdemes megemlítened a weboldalad adatkezelési tájékoztatójában.', 'mesterjelszo' ); ?></p>
					</div>

				</section>

			</div>

			<!-- ===================== ÉLŐ ELŐNÉZET ===================== -->
			<aside class="mjz-preview-column">
				<div class="mjz-preview-sticky">
					<h2 class="mjz-preview-title"><?php esc_html_e( 'Élő előnézet', 'mesterjelszo' ); ?></h2>
					<p class="mjz-field-description"><?php esc_html_e( 'Így fogja látni a látogató a jelszókérő felületet (a valós háttérkép/logó mentés után jelenik meg pontosan).', 'mesterjelszo' ); ?></p>

					<div class="mjz-preview-frame" id="mjz-preview-frame">
						<div class="mjz-preview-bg" id="mjz-preview-bg"></div>
						<div class="mjz-preview-card" id="mjz-preview-card">
							<div class="mjz-preview-logo" id="mjz-preview-logo"></div>
							<div class="mjz-preview-sitename" id="mjz-preview-sitename"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
							<div class="mjz-preview-lock">🔒</div>
							<div class="mjz-preview-message" id="mjz-preview-message"><?php echo esc_html( wp_strip_all_tags( $settings['message'] ) ); ?></div>
							<div class="mjz-preview-input"><?php esc_html_e( 'Belépési jelszó', 'mesterjelszo' ); ?></div>
							<div class="mjz-preview-button" id="mjz-preview-button"><?php esc_html_e( 'Belépés', 'mesterjelszo' ); ?></div>
						</div>
					</div>
				</div>
			</aside>

		</div>
</div>

