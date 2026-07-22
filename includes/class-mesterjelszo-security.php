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
 * - a brute-force támadások elleni próbálkozás-korlátozásért (rate limiting).
 *
 * GDPR megjegyzés: a próbálkozás-korlátozáshoz szükséges azonosítót SOHA nem
 * tároljuk nyers, olvasható IP-címként. Az IP-t kizárólag egy egyirányú,
 * WordPress-sóval (wp_hash) képzett hash formájában, automatikusan lejáró
 * tranzitensekben (transient) kezeljük, ezért abból az eredeti IP cím nem
 * állítható vissza, és az adat magától törlődik a lejárati idő után.
 */
class Mesterjelszo_Security {

	/**
	 * Beállítja (felülírja) a mesterjelszót. A tényleges hitelesítéshez
	 * használt, elsődleges tárolási forma továbbra is a WordPress core
	 * wp_hash_password() függvényével képzett, egyirányú (visszafejthetetlen)
	 * hash - ez nem változott.
	 *
	 * Emellett - kizárólag az admin felületen történő, csapattagok közti
	 * megtekintés lehetővé tételéhez - eltárolunk egy MÁSODIK, visszafejthető
	 * másolatot is, AES-256-CBC titkosítással, a WordPress saját,
	 * wp-config.php-ban tárolt AUTH_KEY/AUTH_SALT értékeiből származtatott
	 * kulccsal. Ez a második másolat SOHA nem vesz részt a látogatói
	 * jelszó-ellenőrzésben, kizárólag a Mesterjelszo_Security::get_current_password_plaintext()
	 * metóduson keresztül, admin jogosultsággal kérdezhető le.
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

		if ( function_exists( 'openssl_encrypt' ) ) {
			$encrypted = $this->encrypt_password_for_display( $plain_password );

			if ( '' !== $encrypted ) {
				update_option( MESTERJELSZO_PASSWORD_ENCRYPTED_OPTION_KEY, $encrypted, false );
			} else {
				delete_option( MESTERJELSZO_PASSWORD_ENCRYPTED_OPTION_KEY );
			}
		} else {
			// Ha a szerveren nincs elérhető OpenSSL bővítmény, a
			// megtekinthető másolatot nem tudjuk előállítani - ilyenkor a
			// funkció automatikusan inaktív marad, a hash-elt forma viszont
			// továbbra is rendben tárolódik és működik.
			delete_option( MESTERJELSZO_PASSWORD_ENCRYPTED_OPTION_KEY );
		}
	}

	/**
	 * A titkosításhoz/visszafejtéshez használt kulcs előállítása a
	 * WordPress saját, kizárólag a wp-config.php fájlban (tehát NEM az
	 * adatbázisban) tárolt titkos kulcsaiból. Ez védelmi réteget ad: egy
	 * pusztán adatbázis-szintű adatszivárgás önmagában nem elegendő a
	 * titkosított mesterjelszó visszafejtéséhez.
	 *
	 * @return string 32 bájt hosszú, nyers bináris kulcs (AES-256-hoz).
	 */
	protected function get_encryption_key(): string {
		$material  = defined( 'AUTH_KEY' ) && AUTH_KEY ? AUTH_KEY : 'mesterjelszo-fallback-auth-key';
		$material .= defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : 'mesterjelszo-fallback-auth-salt';
		$material .= defined( 'SECURE_AUTH_KEY' ) && SECURE_AUTH_KEY ? SECURE_AUTH_KEY : '';

		return hash( 'sha256', $material, true );
	}

	/**
	 * A jelszó AES-256-CBC titkosítása, base64-kódolt, az IV-t is
	 * tartalmazó formában való visszaadása.
	 *
	 * @param string $plain_password A titkosítandó jelszó.
	 * @return string A titkosított, base64-kódolt érték, vagy üres string hiba esetén.
	 */
	protected function encrypt_password_for_display( string $plain_password ): string {
		if ( '' === $plain_password || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$key = $this->get_encryption_key();
		$iv  = random_bytes( 16 );

		$cipher_text = openssl_encrypt( $plain_password, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher_text ) {
			return '';
		}

		return base64_encode( $iv . $cipher_text ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * A titkosított jelszó visszafejtése.
	 *
	 * @param string $encoded A base64-kódolt, titkosított érték.
	 * @return string A visszafejtett jelszó, vagy üres string hiba esetén.
	 */
	protected function decrypt_password_for_display( string $encoded ): string {
		if ( '' === $encoded || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $raw || strlen( $raw ) <= 16 ) {
			return '';
		}

		$iv          = substr( $raw, 0, 16 );
		$cipher_text = substr( $raw, 16 );
		$key         = $this->get_encryption_key();

		$plain = openssl_decrypt( $cipher_text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		return false === $plain ? '' : $plain;
	}

	/**
	 * A jelenleg érvényes mesterjelszó visszaadása nyílt szövegként, KIZÁRÓLAG
	 * admin felületi megtekintésre szánva. A hívó félnek (Mesterjelszo_Admin)
	 * kötelessége a manage_options jogosultság és a nonce ellenőrzése, mielőtt
	 * ezt a metódust meghívja.
	 *
	 * @return string A jelenlegi mesterjelszó, vagy üres string, ha nem
	 *                érhető el (pl. nincs beállítva, vagy a szerveren nincs
	 *                OpenSSL támogatás).
	 */
	public function get_current_password_plaintext(): string {
		$encoded = get_option( MESTERJELSZO_PASSWORD_ENCRYPTED_OPTION_KEY, '' );

		if ( empty( $encoded ) ) {
			return '';
		}

		return $this->decrypt_password_for_display( $encoded );
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
	 * Kliens IP címének lekérése. Elsődlegesen a próbálkozás-korlátozáshoz
	 * (rate limiting) és a megbízható IP-lista ellenőrzéséhez használjuk;
	 * a bejelentkezési napló (Mesterjelszo_Login_Log) is ezt hívja, hogy
	 * mindenhol egységes legyen az IP-cím meghatározásának logikája.
	 *
	 * @return string
	 */
	public function get_client_ip(): string {
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
	 * Megmondja, hogy a jelenlegi látogató IP-címe szerepel-e az admin
	 * felületen beállított, megbízható IP-címek listáján. Ha igen, a
	 * látogató teljesen átugorhatja a jelszókérő felületet.
	 *
	 * @return bool
	 */
	public function is_trusted_ip(): bool {
		$settings = Mesterjelszo_Admin::get_settings();

		if ( empty( $settings['trusted_ips_enabled'] ) || empty( $settings['trusted_ips'] ) ) {
			return false;
		}

		$ip      = $this->get_client_ip();
		$entries = preg_split( '/[\r\n]+/', (string) $settings['trusted_ips'] );

		foreach ( (array) $entries as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}
			if ( $this->ip_matches( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Megmondja, hogy egy IP-cím megegyezik-e egy szabállyal - a szabály
	 * lehet pontos IP-cím, vagy egy egyszerű IPv4 CIDR jelölés
	 * (pl. "203.0.113.0/24").
	 *
	 * @param string $ip    A vizsgálandó, tényleges IP-cím.
	 * @param string $entry A szabály (pontos IP vagy CIDR).
	 * @return bool
	 */
	protected function ip_matches( string $ip, string $entry ): bool {
		if ( false === strpos( $entry, '/' ) ) {
			return $entry === $ip;
		}

		$parts = explode( '/', $entry, 2 );

		if ( 2 !== count( $parts ) ) {
			return false;
		}

		list( $subnet, $mask_bits ) = $parts;

		if (
			! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			! ctype_digit( $mask_bits )
		) {
			return false; // IPv6 CIDR-t egyszerűsítésként nem támogatunk.
		}

		$mask_bits = (int) $mask_bits;

		if ( $mask_bits < 0 || $mask_bits > 32 ) {
			return false;
		}

		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask        = ( 0 === $mask_bits ) ? 0 : ( -1 << ( 32 - $mask_bits ) );

		return ( $ip_long & $mask ) === ( $subnet_long & $mask );
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
	 * @param bool $remember Ha true ÉS az admin bekapcsolta az "Emlékezz rám"
	 *                       funkciót, a munkamenet a beállított napok számáig
	 *                       (alapértelmezetten 15 nap) érvényes marad a
	 *                       szokásos, óra-alapú munkamenet-hossz helyett.
	 *                       Az admin beállítása mindig felülbírálja a
	 *                       kliens által küldött értéket - ha a funkció ki
	 *                       van kapcsolva, a $remember=true érték hatástalan.
	 * @return void
	 */
	public function create_session( bool $remember = false ): void {
		$settings = Mesterjelszo_Admin::get_settings();

		if ( $remember && ! empty( $settings['remember_me_enabled'] ) ) {
			$days     = max( 1, (int) $settings['remember_me_days'] );
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
