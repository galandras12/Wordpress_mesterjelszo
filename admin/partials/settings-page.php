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

	<form method="post" action="options.php" id="mjz-settings-form" novalidate>
		<?php settings_fields( 'mesterjelszo_settings_group' ); ?>

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
				</nav>

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
							<p class="mjz-field-description"><?php esc_html_e( 'Ennyi óráig marad feloldva a weboldal egy látogató böngészőjében a sikeres jelszó megadása után.', 'mesterjelszo' ); ?></p>
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

					<div class="mjz-card-box mjz-info-box">
						<h2><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Tudnivalók', 'mesterjelszo' ); ?></h2>
						<ul>
							<li><?php esc_html_e( 'A mesterjelszó egyirányú, a WordPress core jelszó-kezelésével megegyező hash formában kerül tárolásra - visszafejthető formában soha.', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'A védelem kiterjed az oldalakra, bejegyzésekre, egyedi tartalomtípusokra, a bejelentkezési felületre és a WordPress REST API-ra.', 'mesterjelszo' ); ?></li>
							<li><?php esc_html_e( 'A közvetlenül, webszerver szinten kiszolgált statikus fájlok (pl. a feltöltési mappában lévő képek közvetlen linkje) védelméhez a webszerver (Apache/Nginx) szintű beállítás is szükséges lehet, mivel ezeket a WordPress alapból nem PHP-n keresztül szolgálja ki.', 'mesterjelszo' ); ?></li>
						</ul>
					</div>

				</section>

				<p class="submit mjz-submit-row">
					<?php submit_button( __( 'Beállítások mentése', 'mesterjelszo' ), 'primary mjz-save-button', 'submit', false ); ?>
				</p>

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
	</form>
</div>
