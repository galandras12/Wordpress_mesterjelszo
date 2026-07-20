<?php
/**
 * Biztonsági réteg: jelszó kezelés, munkamenet-kezelés és brute-force védelem.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Security
 *
 * Ez az osztály felel:
 * - a mesterjelszó biztonságos, egyirányú hash alapú (a WordPress core
 *   jelszó-kezelési sztenderdjének megfelelő) tárolásáért és ellenőrzéséért;
 * - a látogatói munkamenetek (session) létrehozásáért és validálásáért;
 * - a brute-force támadások elleni próbálkozás-korlátozásért (rate limiting);
 * - a "Jegyezz meg" (Remember Me) funkció kezeléséért.
 *
 * GDPR megjegyzés: a próbálkozás-korlátozáshoz szükséges azonosítót SOHA nem
 * tároljuk nyers, olvasható IP-címként. Az IP-t kizárólag egy egyirányú,
 * WordPress-sóval (wp_hash) képzett hash formájában, automatikusan lejáró
 * tranzitensekben (transient) kezeljük, ezért abból az eredeti IP cím nem
 * állítható vissza, és az adat magától törlődik a lejárati idő után.
 */
class Mesterjelszo_Security {

	/**
	 * Beállítja (felülírja) a mesterjelszót. A jelszót SOHA nem tároljuk
	 * visszafejthető ("titkosított, de visszafejthető") formában - ehelyett
	 * a WordPress core wp_hash_password() függvényét használjuk, amely
	 * ugyanazt az egyirányú hash-elési sztenderdet alkalmazza, mint amit a
	 * WordPress a felhasználói jelszavak tárolására is használ. Ez a
	 * WordPress által ajánlott, biztonságos módszer - egy visszafejthető
	 * titkosítás ennél kevésbé lenne biztonságos, mert az adatbázishoz
	 * hozzáférő támadó vissza tudná fejteni a jelszót.
	 *
	 * @param string $plain_password A beállítandó jelszó nyílt szövegként.
	 * @return void
	 */
	public function set_password( string $plain_password ): void {
		if ( '' === trim( $plain_password ) ) {
			return;
		}

		if ( ! function_exists( 'wp_hash_password' ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
		}

		$hash = wp_hash_password( $plain_password );
		update_option( MESTERJELSZO_PASSWORD_OPTION_KEY, $hash, false );
	}

	/**
	 * Megmondja, hogy van-e egyáltalán mesterjelszó beállítva.
	 *
	 * @return bool
	 */
	public function has_password(): bool {
		$hash = get_option( MESTERJELSZO_PASSWORD_OPTION_KEY, '' );
		return ! empty( $hash );
	}

	/**
	 * Ellenőrzi a megadott jelszót a tárolt hash alapján.
	 *
	 * @param string $plain_password A látogató által beírt jelszó.
	 * @return bool
	 */
	public function verify_password( string $plain_password ): bool {
		$hash = get_option( MESTERJELSZO_PASSWORD_OPTION_KEY, '' );

		if ( empty( $hash ) || '' === trim( $plain_password ) ) {
			return false;
		}

		return wp_check_password( $plain_password, $hash );
	}

	/**
	 * Kliens IP címének lekérése kizárólag ideiglenes, futásidejű
	 * felhasználásra (rate limiting kulcs generálásához) - az IP-t magát
	 * soha nem tároljuk el.
	 *
	 * @return string
	 */
	protected function get_client_ip(): string {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * A próbálkozás-számlálóhoz tartozó, anonimizált tranzitens-kulcs.
	 *
	 * @return string
	 */
	protected function get_rate_limit_key(): string {
		return 'mesterjelszo_attempts_' . md5( wp_hash( $this->get_client_ip() . 'mesterjelszo-attempts-salt' ) );
	}

	/**
	 * A zároláshoz tartozó, anonimizált tranzitens-kulcs.
	 *
	 * @return string
	 */
	protected function get_lockout_key(): string {
		return 'mesterjelszo_lockout_' . md5( wp_hash( $this->get_client_ip() . 'mesterjelszo-lockout-salt' ) );
	}

	/**
	 * Megmondja, hogy a jelenlegi látogató jelenleg zárolva van-e túl sok
	 * sikertelen próbálkozás miatt.
	 *
	 * @return bool
	 */
	public function is_locked_out(): bool {
		return (bool) get_transient( $this->get_lockout_key() );
	}

	/**
	 * A hátralévő zárolási idő percben (0, ha nincs aktív zárolás).
	 *
	 * @return int
	 */
	public function get_lockout_remaining_minutes(): int {
		$timeout = get_option( '_transient_timeout_' . $this->get_lockout_key() );

		if ( ! $timeout ) {
			return 0;
		}

		$remaining_seconds = (int) $timeout - time();

		return (int) max( 0, ceil( $remaining_seconds / MINUTE_IN_SECONDS ) );
	}

	/**
	 * Sikertelen próbálkozás rögzítése, és szükség esetén zárolás beállítása
	 * a beállításokban megadott próbálkozás-limit elérésekor.
	 *
	 * @return void
	 */
	public function register_failed_attempt(): void {
		$settings     = Mesterjelszo_Admin::get_settings();
		$max_attempts = max( 1, (int) $settings['max_attempts'] );
		$lockout_mins = max( 1, (int) $settings['lockout_duration'] );

		$key      = $this->get_rate_limit_key();
		$attempts = (int) get_transient( $key );
		++$attempts;

		// A próbálkozás-számláló legfeljebb 15 percig "él" - utána magától nullázódik.
		set_transient( $key, $attempts, 15 * MINUTE_IN_SECONDS );

		if ( $attempts >= $max_attempts ) {
			set_transient( $this->get_lockout_key(), 1, $lockout_mins * MINUTE_IN_SECONDS );
			delete_transient( $key );
		}
	}

	/**
	 * Próbálkozás-számláló nullázása sikeres belépés után.
	 *
	 * @return void
	 */
	public function reset_attempts(): void {
		delete_transient( $this->get_rate_limit_key() );
	}

	/**
	 * Új, sikeres munkamenetet hoz létre a látogató számára:
	 * 1) generál egy kriptográfiailag megfelelő véletlen tokent,
	 * 2) a token hash-elt (sha256) változatát tárolja egy lejáró
	 *    tranzitensben (magát a nyers tokent nem tároljuk szerver oldalon),
	 * 3) a nyers tokent egy biztonságos, HttpOnly, SameSite=Strict sütiben
	 *    helyezi el a látogató böngészőjében.
	 *
	 * @param bool $remember_me Ha true, a "Jegyezz meg" napok számát használja.
	 * @return void
	 */
	public function create_session( bool $remember_me = false ): void {
		$settings = Mesterjelszo_Admin::get_settings();

		// Ha "Jegyezz meg" be van jelölve és engedélyezve van
		if ( $remember_me && ! empty( $settings['remember_me_enabled'] ) ) {
			$days    = max( 1, (int) $settings['remember_me_days'] );
			$lifetime = $days * DAY_IN_SECONDS;
		} else {
			$hours    = max( 1, (int) $settings['session_duration'] );
			$lifetime = $hours * HOUR_IN_SECONDS;
		}

		$token      = wp_generate_password( 64, false, false );
		$token_hash = hash( 'sha256', $token );
		$transient  = 'mesterjelszo_session_' . $token_hash;

		set_transient( $transient, 1, $lifetime );

		$secure      = is_ssl();
		$cookie_path = ( defined( 'COOKIEPATH' ) && COOKIEPATH ) ? COOKIEPATH : '/';
		$domain      = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';

		if ( ! headers_sent() ) {
			setcookie(
				MESTERJELSZO_COOKIE_NAME,
				$token,
				array(
					'expires'  => time() + $lifetime,
					'path'     => $cookie_path,
					'domain'   => $domain,
					'secure'   => $secure,
					'httponly' => true,
					'samesite' => 'Strict',
				)
			);
		}

		// A jelenlegi kérésen belül is azonnal "feloldottá" tesszük a
		// látogatót, hogy az AJAX válasz utáni átirányítás biztosan
		// átengedje a tartalmat.
		$_COOKIE[ MESTERJELSZO_COOKIE_NAME ] = $token;
	}

	/**
	 * Megmondja, hogy a jelenlegi látogató rendelkezik-e érvényes,
	 * feloldott munkamenettel.
	 *
	 * @return bool
	 */
	public function is_unlocked(): bool {
		if ( empty( $_COOKIE[ MESTERJELSZO_COOKIE_NAME ] ) ) {
			return false;
		}

		$token      = sanitize_text_field( wp_unslash( $_COOKIE[ MESTERJELSZO_COOKIE_NAME ] ) );
		$token_hash = hash( 'sha256', $token );

		return (bool) get_transient( 'mesterjelszo_session_' . $token_hash );
	}
}
