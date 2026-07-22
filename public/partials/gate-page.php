<?php
/**
 * A látogatók számára megjelenő, teljes képernyős jelszókérő felület.
 *
 * Ez a fájl egy ÖNÁLLÓ HTML dokumentumot állít elő - szándékosan nem hívja
 * meg a téma get_header()/get_footer() függvényeit, mert:
 * 1) így garantáltan nem jelenik meg semmilyen védett tartalom (menü,
 *    widget, kereső stb.) a jelszó megadása előtt, függetlenül a
 *    telepített témától;
 * 2) a téma esetleges hibái vagy lassú betöltésű elemei nem befolyásolják
 *    a jelszókérő felület megjelenését és sebességét.
 *
 * A változó ($settings) a Mesterjelszo_Public::render_gate_and_exit()
 * metódusból érkezik.
 *
 * @package Mesterjelszo
 * @var array $settings A mentett plugin-beállítások.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );

$logo_url = '';
if ( ! empty( $settings['logo_id'] ) ) {
	$maybe_logo_url = wp_get_attachment_image_url( (int) $settings['logo_id'], 'medium' );
	if ( $maybe_logo_url ) {
		$logo_url = $maybe_logo_url;
	}
}

$bg_image_url = '';
if ( 'image' === $settings['bg_type'] && ! empty( $settings['bg_image_id'] ) ) {
	$maybe_bg_url = wp_get_attachment_image_url( (int) $settings['bg_image_id'], 'full' );
	if ( $maybe_bg_url ) {
		$bg_image_url = $maybe_bg_url;
	}
}

$body_classes = array( 'mjz-body' );
if ( 'dark' === $settings['color_mode'] ) {
	$body_classes[] = 'mjz-dark';
} elseif ( 'light' === $settings['color_mode'] ) {
	$body_classes[] = 'mjz-light';
} else {
	$body_classes[] = 'mjz-auto';
}

$security             = new Mesterjelszo_Security();
$is_locked_out         = $security->is_locked_out();
$lockout_minutes_left  = $is_locked_out ? $security->get_lockout_remaining_minutes() : 0;

// Az eredeti, meghívott URL összeállítása, hogy sikeres jelszó megadása
// után a látogató pontosan oda kerüljön vissza, ahova eredetileg tartott.
$scheme       = is_ssl() ? 'https://' : 'http://';
$host         = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
$redirect_to  = $host ? esc_url_raw( $scheme . $host . $request_uri ) : home_url( '/' );

$nonce = wp_create_nonce( 'mesterjelszo_gate_nonce' );

$opacity_value = max( 0, min( 100, (int) $settings['bg_opacity'] ) ) / 100;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $site_name ); ?> &ndash; <?php esc_html_e( 'Jelszóval védett terület', 'mesterjelszo' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( MESTERJELSZO_PLUGIN_URL . 'public/css/mesterjelszo-public.css?v=' . MESTERJELSZO_VERSION ); ?>">
	<style>
		:root {
			--mjz-accent: <?php echo esc_html( $settings['accent_color'] ); ?>;
			--mjz-text: <?php echo esc_html( $settings['text_color'] ); ?>;
			--mjz-bg-color: <?php echo esc_html( $settings['bg_color'] ); ?>;
			--mjz-bg-opacity: <?php echo esc_html( (string) $opacity_value ); ?>;
		}
		<?php if ( $bg_image_url ) : ?>
		.mjz-bg-layer {
			background-image: url('<?php echo esc_url( $bg_image_url ); ?>');
			background-size: cover;
			background-position: center center;
			background-repeat: no-repeat;
		}
		<?php else : ?>
		.mjz-bg-layer {
			background-color: var(--mjz-bg-color);
		}
		<?php endif; ?>
	</style>
</head>
<body class="<?php echo esc_attr( implode( ' ', $body_classes ) ); ?>">

	<div class="mjz-bg-layer" aria-hidden="true"></div>

	<main class="mjz-wrapper">
		<div class="mjz-card" role="region" aria-labelledby="mjz-heading">

			<?php if ( $logo_url ) : ?>
				<div class="mjz-logo">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $settings['show_site_name'] ) ) : ?>
				<h1 id="mjz-heading" class="mjz-site-name"><?php echo esc_html( $site_name ); ?></h1>
			<?php else : ?>
				<h1 id="mjz-heading" class="mjz-screen-reader-text"><?php esc_html_e( 'Jelszóval védett weboldal', 'mesterjelszo' ); ?></h1>
			<?php endif; ?>

			<div class="mjz-lock-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="40" height="40" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M6 10V8a6 6 0 1112 0v2M5 10h14a1 1 0 011 1v10a1 1 0 01-1 1H5a1 1 0 01-1-1V11a1 1 0 011-1z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>

			<?php if ( ! empty( $settings['message'] ) ) : ?>
				<div class="mjz-message">
					<?php echo wp_kses_post( $settings['message'] ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $is_locked_out ) : ?>

				<div class="mjz-error mjz-locked" role="alert">
					<?php
					printf(
						/* translators: %d: hátralévő percek száma */
						esc_html__( 'Túl sok sikertelen próbálkozás történt. Kérjük, próbáld újra kb. %d perc múlva.', 'mesterjelszo' ),
						(int) $lockout_minutes_left
					);
					?>
				</div>

			<?php else : ?>

				<form id="mjz-form" class="mjz-form"
					data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-redirect="<?php echo esc_url( $redirect_to ); ?>"
					data-empty-message="<?php echo esc_attr__( 'Kérjük, add meg a jelszót.', 'mesterjelszo' ); ?>"
					novalidate
				>
					<label for="mjz-password" class="mjz-label"><?php esc_html_e( 'Belépési jelszó', 'mesterjelszo' ); ?></label>

					<div class="mjz-input-row">
						<input
							type="password"
							id="mjz-password"
							name="mesterjelszo_password"
							class="mjz-input"
							autocomplete="current-password"
							autofocus
							required
							aria-describedby="mjz-error"
						>
						<button type="button" class="mjz-toggle-visibility" id="mjz-toggle-visibility" aria-label="<?php echo esc_attr__( 'Jelszó megjelenítése / elrejtése', 'mesterjelszo' ); ?>" aria-pressed="false">
							<svg class="mjz-eye-open" viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg>
							<svg class="mjz-eye-closed" viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden><path d="M3 3l18 18M10.6 10.6a3 3 0 004.24 4.24M9.9 5.1A11 11 0 0123 12s-1.4 2.5-4 4.5M6.1 6.6C3.6 8.3 2 12 2 12s3.5 6.5 10 6.5c1.2 0 2.3-.2 3.4-.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</button>
					</div>

					<div id="mjz-error" class="mjz-error" role="alert" aria-live="polite" hidden></div>

					<?php if ( ! empty( $settings['remember_me_enabled'] ) ) : ?>
						<label class="mjz-remember-row">
							<input type="checkbox" id="mjz-remember" name="remember_me" value="1">
							<span>
								<?php
								printf(
									/* translators: %d: napok száma */
									esc_html__( 'Emlékezz rám %d napig ezen az eszközön', 'mesterjelszo' ),
									(int) $settings['remember_me_days']
								);
								?>
							</span>
						</label>
					<?php endif; ?>

					<button type="submit" id="mjz-submit" class="mjz-submit">
						<span class="mjz-spinner" id="mjz-spinner" hidden aria-hidden="true"></span>
						<span class="mjz-submit-text"><?php esc_html_e( 'Belépés', 'mesterjelszo' ); ?></span>
					</button>
				</form>

			<?php endif; ?>

			<p class="mjz-footer-note">
				<?php
				printf(
					/* translators: %1$s: évszám, %2$s: weboldal neve */
					esc_html__( '© %1$s %2$s', 'mesterjelszo' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( $site_name )
				);
				?>
			</p>

		</div>
	</main>

	<script src="<?php echo esc_url( MESTERJELSZO_PLUGIN_URL . 'public/js/mesterjelszo-public.js?v=' . MESTERJELSZO_VERSION ); ?>" defer></script>
</body>
</html>
