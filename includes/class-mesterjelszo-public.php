<?php
/**
 * A weboldal látogatói oldalát érintő logika: a jelszókérő "kapu" (gate)
 * megjelenítése, a REST API és a bejelentkezési felület zárolása,
 * szelektív REST API engedélyezés (Jetpack stb.), "Jegyezz meg" funkció.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Public
 */
class Mesterjelszo_Public {

	/**
	 * A biztonsági osztály példánya.
	 *
	 * @var Mesterjelszo_Security
	 */
	protected $security;

	/**
	 * Konstruktor.
	 *
	 * @param Mesterjelszo_Security $security A biztonsági osztály példánya.
	 */
	public function __construct( Mesterjelszo_Security $security ) {
		$this->security = $security;
	}

	/**
	 * Publikus oldali hook-ok regisztrálása.
	 *
	 * @return void
	 */
	public function init(): void {
		// Az 'init' hook a legtöbb WordPress belépési ponton lefut: a
		// normál oldalbetöltésen, a wp-login.php-n és az admin-ajax.php-n
		// keresztül is - ez teszi lehetővé, hogy egyetlen helyen kezeljük a
		// teljes oldal, valamint a bejelentkezési felület zárolását.
		add_action( 'init', array( $this, 'maybe_gate' ) );

		// A REST API-t külön, a hivatalos hitelesítési szűrőn keresztül
		// védjük, hogy a kérés tiszta, szabványos JSON 401-es hibát kapjon
		// HTML jelszókérő oldal helyett.
		add_filter( 'rest_authentication_errors', array( $this, 'protect_rest_api' ) );

		// A jelszó-ellenőrző AJAX végpont regisztrálása bejelentkezett és
		// nem bejelentkezett (nopriv) látogatók számára egyaránt.
		add_action( 'wp_ajax_mesterjelszo_verify', array( $this, 'ajax_verify_password' ) );
		add_action( 'wp_ajax_nopriv_mesterjelszo_verify', array( $this, 'ajax_verify_password' ) );
	}

	/**
	 * Eldönti, hogy a jelenlegi kérést zárolni kell-e, és ha igen,
	 * megjeleníti a jelszókérő felületet (vagy AJAX kérés esetén JSON hibát
	 * ad vissza), majd leállítja a további feldolgozást.
	 *
	 * @return void
	 */
	public function maybe_gate(): void {
		// Ütemezett (cron) feladatok soha ne akadjanak el a zár miatt.
		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return;
		}

		// A REST API kéréseket nem itt, hanem a protect_rest_api() szűrőn
		// keresztül kezeljük, hogy szabványos JSON választ kapjanak.
		if ( $this->is_rest_request() ) {
			return;
		}

		$doing_ajax  = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$ajax_action = ( $doing_ajax && isset( $_REQUEST['action'] ) )
			? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) )
			: '';

		// A saját ellenőrző AJAX végpontunkat mindig át kell engedni,
		// különben a látogató soha nem tudná beküldeni a jelszót.
		if ( $doing_ajax && 'mesterjelszo_verify' === $ajax_action ) {
			return;
		}

		$settings = Mesterjelszo_Admin::get_settings();

		// Ha a védelem ki van kapcsolva, vagy még nincs mesterjelszó
		// beállítva, nincs mit zárolni.
		if ( empty( $settings['enabled'] ) || ! $this->security->has_password() ) {
			return;
		}

		// A látogatónak már van érvényes, feloldott munkamenete.
		if ( $this->security->is_unlocked() ) {
			return;
		}

		// Beállítástól függően a bejelentkezett, jogosult adminisztrátorok
		// teljesen átugorhatják a jelszókérő felületet.
		if ( ! empty( $settings['bypass_admins'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Biztonsági háló: a wp-admin irányítópultot SOHA nem zárjuk ki
		// jogosult adminisztrátorok elől - ellenkező esetben a tulajdonos
		// akár ki is zárhatná saját magát a saját weboldaláról.
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Minden más, nem hitelesített AJAX kérést (pl. más bővítmények API-
		// szerű végpontjait) is elutasítunk zárolt állapotban.
		if ( $doing_ajax ) {
			wp_send_json_error(
				array( 'message' => __( 'Hozzáférés megtagadva: a weboldal jelszóval védett.', 'mesterjelszo' ) ),
				403
			);
		}

		$this->render_gate_and_exit();
	}

	/**
	 * Megmondja, hogy a jelenlegi kérés a WordPress REST API-nak szól-e.
	 * A REST_REQUEST konstans csak a parse_request hook után áll
	 * rendelkezésre, ami KÉSŐBB fut le, mint az 'init' - ezért az URL
	 * mintázata alapján is ellenőrzünk, hogy már 'init' időpontban is
	 * felismerjük a REST kéréseket.
	 *
	 * @return bool
	 */
	protected function is_rest_request(): bool {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$uri         = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		return ( false !== strpos( $uri, $rest_prefix ) ) || ( false !== strpos( $uri, 'rest_route=' ) );
	}

	/**
	 * Megmondja, hogy a jelenlegi REST kérés engedélyezett-e a szelektív
	 * REST API engedélyezés miatt (pl. Jetpack).
	 *
	 * @return bool
	 */
	protected function is_rest_request_allowed(): bool {
		$settings = Mesterjelszo_Admin::get_settings();

		// Ha a REST API engedélyezés kikapcsolt, nem engedélyezünk semmit
		if ( empty( $settings['rest_api_enabled'] ) ) {
			return false;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Jetpack API végpontok engedélyezése
		$allowed_patterns = array(
			'/wp-json/jetpack/',
		);

		foreach ( $allowed_patterns as $pattern ) {
			if ( false !== strpos( $uri, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A REST API kérések védelme szabványos hitelesítési hiba
	 * visszaadásával, ha a látogató nincs feloldva (és nem engedélyezett).
	 *
	 * @param mixed $result A szűrőláncban addig felhalmozott eredmény.
	 * @return mixed
	 */
	public function protect_rest_api( $result ) {
		// Ha egy korábbi szűrő már hibát állított be, azt nem írjuk felül.
		if ( ! empty( $result ) ) {
			return $result;
		}

		$settings = Mesterjelszo_Admin::get_settings();

		if ( empty( $settings['enabled'] ) || ! $this->security->has_password() ) {
			return $result;
		}

		if ( $this->security->is_unlocked() ) {
			return $result;
		}

		if ( ! empty( $settings['bypass_admins'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return $result;
		}

		// Ha a szelektív REST API engedélyezés be van kapcsolva, ellenőrizzük
		if ( ! $this->is_rest_request_allowed() ) {
			return new WP_Error(
				'mesterjelszo_rest_locked',
				__( 'A weboldal jelszóval védett. A REST API jelenleg nem érhető el.', 'mesterjelszo' ),
				array( 'status' => 401 )
			);
		}

		return $result;
	}

	/**
	 * A jelszó-ellenőrző AJAX végpont. Nonce-t és brute-force limitet
	 * ellenőriz, sikeres jelszó esetén munkamenetet hoz létre.
	 * Támogatja a "Jegyezz meg" funkcionalitást.
	 *
	 * @return void
	 */
	public function ajax_verify_password(): void {
		check_ajax_referer( 'mesterjelszo_gate_nonce', 'nonce' );

		if ( $this->security->is_locked_out() ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: hátralévő percek száma */
						__( 'Túl sok sikertelen próbálkozás történt. Kérjük, próbáld újra kb. %d perc múlva.', 'mesterjelszo' ),
						$this->security->get_lockout_remaining_minutes()
					),
				),
				429
			);
		}

		$password = isset( $_POST['mesterjelszo_password'] )
			? sanitize_text_field( wp_unslash( $_POST['mesterjelszo_password'] ) )
			: '';

		if ( '' === $password ) {
			wp_send_json_error( array( 'message' => __( 'Kérjük, add meg a jelszót.', 'mesterjelszo' ) ), 400 );
		}

		if ( $this->security->verify_password( $password ) ) {
			$this->security->reset_attempts();

			// Ellenőrizzük, hogy bejelölt-e "Jegyezz meg"
			$remember_me = isset( $_POST['mesterjelszo_remember_me'] ) && 
							'1' === sanitize_text_field( wp_unslash( $_POST['mesterjelszo_remember_me'] ) );

			$this->security->create_session( $remember_me );

			$redirect = isset( $_POST['redirect_to'] )
				? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
				: home_url( '/' );

			wp_send_json_success( array( 'redirect' => $redirect ) );
		}

		$this->security->register_failed_attempt();

		wp_send_json_error( array( 'message' => __( 'Hibás jelszó. Kérjük, próbáld újra.', 'mesterjelszo' ) ), 403 );
	}

	/**
	 * A jelszókérő felület kirenderelése és a kérés leállítása.
	 *
	 * A választ nocache fejlécekkel és 503-as státuszkóddal (a szabványos
	 * "ideiglenesen nem elérhető" HTTP állapottal, Retry-After fejléccel)
	 * küldjük ki, hogy a keresőmotorok ne indexeljék a jelszókérő oldalt a
	 * ténylegesen mögötte lévő tartalom helyett.
	 *
	 * @return void
	 */
	protected function render_gate_and_exit(): void {
		$settings = Mesterjelszo_Admin::get_settings();

		nocache_headers();

		if ( ! headers_sent() ) {
			header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
			header( 'Retry-After: 3600' );
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

		include MESTERJELSZO_PLUGIN_DIR . 'public/partials/gate-page.php';
		exit;
	}
}
