<?php
/**
 * Az adminisztrációs felület logikája: beállítások regisztrálása, mentése,
 * menüpont és a beállítási oldal megjelenítése.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Admin
 */
class Mesterjelszo_Admin {

	/**
	 * A biztonsági osztály példánya.
	 *
	 * @var Mesterjelszo_Security
	 */
	protected $security;

	/**
	 * Egy kérésen belüli beállítás-gyorsítótár, hogy ne kelljen többször
	 * lekérdezni az adatbázisból ugyanazt az opciót.
	 *
	 * @var array|null
	 */
	protected static $settings_cache = null;

	/**
	 * Konstruktor.
	 *
	 * @param Mesterjelszo_Security $security A biztonsági osztály példánya.
	 */
	public function __construct( Mesterjelszo_Security $security ) {
		$this->security = $security;
	}

	/**
	 * Admin hook-ok regisztrálása.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_missing_password_notice' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( MESTERJELSZO_PLUGIN_FILE ),
			array( $this, 'add_settings_link' )
		);
	}

	/**
	 * Alapértelmezett beállítások tömbje.
	 *
	 * @return array
	 */
	public static function get_default_settings(): array {
		return array(
			'enabled'          => true,
			'logo_id'          => 0,
			'show_site_name'   => true,
			'message'          => __( 'Ez a weboldal jelszóval védett. Kérjük, add meg a hozzáférési jelszót a tartalom megtekintéséhez.', 'mesterjelszo' ),
			'bg_type'          => 'color',
			'bg_color'         => '#1a1c2c',
			'bg_image_id'      => 0,
			'bg_opacity'       => 100,
			'color_mode'       => 'dark',
			'accent_color'     => '#6c5ce7',
			'text_color'       => '#ffffff',
			'session_duration' => 24,
			'max_attempts'     => 5,
			'lockout_duration' => 15,
			'bypass_admins'    => true,
		);
	}

	/**
	 * A mentett beállítások lekérése, az alapértékekkel kiegészítve, hogy
	 * mindig teljes és biztonságosan használható tömböt kapjunk vissza.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$saved = get_option( MESTERJELSZO_OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		self::$settings_cache = wp_parse_args( $saved, self::get_default_settings() );

		return self::$settings_cache;
	}

	/**
	 * Admin menüpont regisztrálása a bal oldali menüsávban.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Mesterjelszó beállítások', 'mesterjelszo' ),
			__( 'Mesterjelszó', 'mesterjelszo' ),
			'manage_options',
			'mesterjelszo',
			array( $this, 'render_settings_page' ),
			'dashicons-lock',
			80
		);
	}

	/**
	 * "Beállítások" gyorslink hozzáadása a bővítmények listájában megjelenő
	 * sorhoz.
	 *
	 * @param array $links A plugin sorában már meglévő linkek.
	 * @return array
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=mesterjelszo' ) ),
			esc_html__( 'Beállítások', 'mesterjelszo' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * A WordPress Settings API regisztrációja saját szanitizáló callback-kel.
	 * A settings_fields() függvény (amit a settings-page.php partial hív meg)
	 * automatikusan elhelyezi a szükséges nonce mezőt, amit a WordPress core
	 * options.php feldolgozó fájlja ellenőriz mentéskor - így külön,
	 * manuális nonce-ellenőrzésre itt nincs szükség.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'mesterjelszo_settings_group',
			MESTERJELSZO_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);
	}

	/**
	 * A beküldött beállítások szanitizálása, validálása, valamint a
	 * mesterjelszó mező külön kezelése.
	 *
	 * A jogosultság-ellenőrzést (manage_options) a menü capability
	 * paramétere, a nonce-ellenőrzést pedig a WordPress options.php
	 * feldolgozó fájlja automatikusan elvégzi a settings_fields() által
	 * kiírt nonce mező alapján, mielőtt ez a callback egyáltalán lefutna.
	 *
	 * @param mixed $input Nyers, nem megbízható bemenet a $_POST-ból.
	 * @return array A megtisztított, tárolásra kész beállítás-tömb.
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::get_default_settings();
		$output   = array();

		$output['enabled']        = ! empty( $input['enabled'] );
		$output['logo_id']        = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
		$output['show_site_name'] = ! empty( $input['show_site_name'] );

		// Az üzenet mezőben korlátozott HTML-t engedélyezünk (kiemelés,
		// hivatkozás, bekezdés, sortörés), a veszélyes elemeket (script,
		// iframe stb.) a wp_kses_post() automatikusan eltávolítja.
		$output['message'] = isset( $input['message'] )
			? wp_kses_post( wp_unslash( $input['message'] ) )
			: $defaults['message'];

		$output['bg_type'] = ( isset( $input['bg_type'] ) && 'image' === $input['bg_type'] )
			? 'image'
			: 'color';

		$bg_color            = isset( $input['bg_color'] ) ? sanitize_hex_color( wp_unslash( $input['bg_color'] ) ) : '';
		$output['bg_color']  = $bg_color ? $bg_color : $defaults['bg_color'];
		$output['bg_image_id'] = isset( $input['bg_image_id'] ) ? absint( $input['bg_image_id'] ) : 0;

		$opacity              = isset( $input['bg_opacity'] ) ? absint( $input['bg_opacity'] ) : 100;
		$output['bg_opacity'] = min( 100, max( 0, $opacity ) );

		$allowed_modes         = array( 'light', 'dark', 'auto' );
		$output['color_mode']  = ( isset( $input['color_mode'] ) && in_array( $input['color_mode'], $allowed_modes, true ) )
			? $input['color_mode']
			: $defaults['color_mode'];

		$accent_color            = isset( $input['accent_color'] ) ? sanitize_hex_color( wp_unslash( $input['accent_color'] ) ) : '';
		$output['accent_color']  = $accent_color ? $accent_color : $defaults['accent_color'];

		$text_color            = isset( $input['text_color'] ) ? sanitize_hex_color( wp_unslash( $input['text_color'] ) ) : '';
		$output['text_color']  = $text_color ? $text_color : $defaults['text_color'];

		$output['session_duration'] = isset( $input['session_duration'] )
			? max( 1, absint( $input['session_duration'] ) )
			: $defaults['session_duration'];

		$output['max_attempts'] = isset( $input['max_attempts'] )
			? max( 1, absint( $input['max_attempts'] ) )
			: $defaults['max_attempts'];

		$output['lockout_duration'] = isset( $input['lockout_duration'] )
			? max( 1, absint( $input['lockout_duration'] ) )
			: $defaults['lockout_duration'];

		$output['bypass_admins'] = ! empty( $input['bypass_admins'] );

		// A mesterjelszót SZÁNDÉKOSAN nem a $input tömbön keresztül,
		// hanem külön $_POST mezőkből dolgozzuk fel, mert ez a mező soha
		// nem kerülhet be nyílt szövegként a mesterjelszo_settings option
		// tömbjébe - kizárólag a saját, dedikált, hash-elt option-jébe.
		if ( ! empty( $_POST['mesterjelszo_master_password'] ) ) {
			$new_password = sanitize_text_field( wp_unslash( $_POST['mesterjelszo_master_password'] ) );
			$confirm      = isset( $_POST['mesterjelszo_master_password_confirm'] )
				? sanitize_text_field( wp_unslash( $_POST['mesterjelszo_master_password_confirm'] ) )
				: '';

			if ( $new_password === $confirm ) {
				$this->security->set_password( $new_password );
				add_settings_error(
					'mesterjelszo_settings_group',
					'mesterjelszo_password_updated',
					__( 'A mesterjelszó sikeresen frissítve lett.', 'mesterjelszo' ),
					'success'
				);
			} else {
				add_settings_error(
					'mesterjelszo_settings_group',
					'mesterjelszo_password_mismatch',
					__( 'A megadott két jelszó nem egyezik meg, ezért a mesterjelszó nem került frissítésre. A többi beállítás elmentésre került.', 'mesterjelszo' ),
					'error'
				);
			}
		}

		self::$settings_cache = null; // A gyorsítótár érvénytelenítése mentés után.

		return $output;
	}

	/**
	 * Figyelmeztetés megjelenítése az admin felület más oldalain, ha a
	 * védelem be van kapcsolva, de még nincs mesterjelszó beállítva.
	 *
	 * @return void
	 */
	public function maybe_show_missing_password_notice(): void {
		$screen = get_current_screen();

		if ( $screen && 'toplevel_page_mesterjelszo' === $screen->id ) {
			return; // A saját beállítási oldalunkon ezt már jelezzük a formban.
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get_settings();

		if ( ! empty( $settings['enabled'] ) && ! $this->security->has_password() ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s %s</p></div>',
				esc_html__( 'A Mesterjelszó bővítmény be van kapcsolva, de még nincs mesterjelszó beállítva - a weboldal jelenleg NEM védett.', 'mesterjelszo' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=mesterjelszo' ) ),
					esc_html__( 'Beállítás most', 'mesterjelszo' )
				)
			);
		}
	}

	/**
	 * Admin CSS/JS betöltése kizárólag a plugin saját beállítási oldalán -
	 * más admin oldalakon szándékosan nem terheljük feleslegesen a böngészőt.
	 *
	 * @param string $hook Az aktuális admin oldal "hook suffix"-e.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_mesterjelszo' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'mesterjelszo-admin',
			MESTERJELSZO_PLUGIN_URL . 'admin/css/mesterjelszo-admin.css',
			array( 'wp-color-picker' ),
			MESTERJELSZO_VERSION
		);

		wp_enqueue_script(
			'mesterjelszo-admin',
			MESTERJELSZO_PLUGIN_URL . 'admin/js/mesterjelszo-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			MESTERJELSZO_VERSION,
			true
		);

		wp_localize_script(
			'mesterjelszo-admin',
			'mesterjelszoAdmin',
			array(
				'mediaTitle'          => __( 'Kép kiválasztása', 'mesterjelszo' ),
				'mediaButton'         => __( 'Kiválasztás', 'mesterjelszo' ),
				'confirmRemove'       => __( 'Biztosan eltávolítod a képet?', 'mesterjelszo' ),
				'passwordMismatch'    => __( 'A két jelszó nem egyezik meg.', 'mesterjelszo' ),
				'passwordTooShort'    => __( 'A jelszónak legalább 6 karakter hosszúnak kell lennie.', 'mesterjelszo' ),
			)
		);
	}

	/**
	 * A beállítási oldal megjelenítése. A tényleges HTML kimenetet egy
	 * külön partial fájl tartalmazza a jobb átláthatóság érdekében.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'mesterjelszo' ) );
		}

		$settings     = self::get_settings();
		$has_password = $this->security->has_password();

		include MESTERJELSZO_PLUGIN_DIR . 'admin/partials/settings-page.php';
	}
}
