<?php
/**
 * Bejelentkezési napló: sikertelen jelszó-próbálkozások rögzítése IP-cím és
 * (késleltetve feltöltött) geolokációs adatok alapján, biztonsági
 * auditálási céllal.
 *
 * FONTOS ADATVÉDELMI MEGJEGYZÉS: ez az osztály - a plugin más részeitől
 * (Mesterjelszo_Security rate-limiting mechanizmusától) eltérően -
 * SZÁNDÉKOSAN a látogatók valódi, olvasható IP-címét tárolja el, mivel ez a
 * funkció kifejezetten biztonsági auditálásra (támadási minták, gyakori
 * támadó IP-k azonosítására) szolgál, admin-only felületen. Az adatok
 * legfeljebb 1 évig kerülnek megőrzésre, utána automatikusan törlődnek.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Login_Log
 */
class Mesterjelszo_Login_Log {

	/**
	 * A napló tárolására szolgáló egyedi adatbázistábla nevének lekérése.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'mesterjelszo_login_log';
	}

	/**
	 * A napló táblájának létrehozása (vagy sémafrissítése) a WordPress
	 * saját dbDelta() segédfüggvényén keresztül, amely biztonságosan,
	 * adatvesztés nélkül kezeli a séma-módosításokat.
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(45) NOT NULL,
			country VARCHAR(100) DEFAULT NULL,
			city VARCHAR(100) DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY ip_address (ip_address),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Egy sikertelen bejelentkezési próbálkozás rögzítése. A geolokációs
	 * adatokat SZÁNDÉKOSAN nem itt, szinkron módon kérdezzük le (ez
	 * lelassítaná a látogató felé adott AJAX választ, és egy külső
	 * szolgáltatás elérhetetlensége esetén akár teljesen el is akadhatna a
	 * folyamat) - ehelyett a backfill_geo_data() metódus tölti fel őket
	 * késleltetve, az admin felület megtekintésekor.
	 *
	 * @param string $ip A látogató IP-címe.
	 * @return void
	 */
	public static function record_attempt( string $ip ): void {
		global $wpdb;

		if ( '' === trim( $ip ) ) {
			return;
		}

		$wpdb->insert(
			self::table_name(),
			array(
				'ip_address' => $ip,
				'country'    => null,
				'city'       => null,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		self::maybe_prune();
	}

	/**
	 * A sikertelen próbálkozások száma az utolsó 1 nap / 1 hét / 1 hónap /
	 * 1 év időszakban.
	 *
	 * @return array{day:int,week:int,month:int,year:int}
	 */
	public static function get_counts(): array {
		global $wpdb;

		$table = self::table_name();
		$now   = current_time( 'mysql' );

		$periods = array(
			'day'   => '1 DAY',
			'week'  => '7 DAY',
			'month' => '30 DAY',
			'year'  => '365 DAY',
		);

		$counts = array();

		foreach ( $periods as $key => $interval ) {
			// Az $interval kizárólag a fenti, kódba írt, fix értékek egyike
			// lehet - felhasználói bemenet soha nem kerül bele, ezért
			// biztonságos közvetlenül a lekérdezésbe illeszteni.
			$counts[ $key ] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(%s, INTERVAL {$interval})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$now
				)
			);
		}

		return $counts;
	}

	/**
	 * A legutóbbi próbálkozások lekérése, legfrissebb elöl.
	 *
	 * @param int $limit Maximálisan visszaadott sorok száma.
	 * @return array
	 */
	public static function get_recent_entries( int $limit = 100 ): array {
		global $wpdb;

		$table = self::table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * A teljes napló törlése (admin felületről, jogosultság- és
	 * nonce-ellenőrzés után hívva).
	 *
	 * @return void
	 */
	public static function clear_log(): void {
		global $wpdb;

		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * 1 évnél régebbi bejegyzések automatikus törlése. Naponta legfeljebb
	 * egyszer fut le ténylegesen (egy tárolt időbélyeg alapján), hogy ne
	 * terhelje feleslegesen az adatbázist minden egyes rögzítéskor.
	 *
	 * @return void
	 */
	protected static function maybe_prune(): void {
		$last = (int) get_option( 'mesterjelszo_log_last_prune', 0 );

		if ( ( time() - $last ) < DAY_IN_SECONDS ) {
			return;
		}

		global $wpdb;
		$table = self::table_name();

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < DATE_SUB(%s, INTERVAL 1 YEAR)", current_time( 'mysql' ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		update_option( 'mesterjelszo_log_last_prune', time(), false );
	}

	/**
	 * A még feldolgozatlan (ország/város nélküli) legutóbbi bejegyzések
	 * geolokációs adatainak késleltetett feltöltése. Ezt a metódust az
	 * admin felület (napló fül) megtekintésekor hívjuk meg, kis limittel,
	 * hogy ne lassítsa érdemben az oldalbetöltést.
	 *
	 * @param int $limit Egyszerre legfeljebb ennyi bejegyzést dolgoz fel.
	 * @return void
	 */
	public static function backfill_geo_data( int $limit = 15 ): void {
		global $wpdb;

		$table = self::table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, ip_address FROM {$table} WHERE country IS NULL ORDER BY id DESC LIMIT %d", $limit )
		);

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$geo = self::lookup_geo( $row->ip_address );

			$wpdb->update(
				$table,
				array(
					'country' => $geo['country'],
					'city'    => $geo['city'],
				),
				array( 'id' => $row->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Egy IP-cím geolokációjának (ország, város) lekérdezése egy külső,
	 * ingyenes, kulcsot nem igénylő szolgáltatáson (ip-api.com) keresztül,
	 * eredményenként 30 napig gyorsítótárazva. Helyi/privát IP-címekre nem
	 * végzünk lekérdezést. Bármilyen hiba esetén "Ismeretlen" értéket ad
	 * vissza - a funkció sosem akaszthatja meg a plugin működését.
	 *
	 * @param string $ip A lekérdezendő IP-cím.
	 * @return array{country:string,city:string}
	 */
	protected static function lookup_geo( string $ip ): array {
		$fallback = array(
			'country' => __( 'Ismeretlen', 'mesterjelszo' ),
			'city'    => __( 'Ismeretlen', 'mesterjelszo' ),
		);

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return $fallback; // Helyi/privát IP-cím - nincs értelme külső lekérdezésnek.
		}

		$cache_key = 'mesterjelszo_geo_' . md5( $ip );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,country,city',
			array( 'timeout' => 3 )
		);

		if ( is_wp_error( $response ) ) {
			return $fallback;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['status'] ) || 'success' !== $body['status'] ) {
			return $fallback;
		}

		$result = array(
			'country' => ! empty( $body['country'] ) ? sanitize_text_field( $body['country'] ) : $fallback['country'],
			'city'    => ! empty( $body['city'] ) ? sanitize_text_field( $body['city'] ) : $fallback['city'],
		);

		set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );

		return $result;
	}
}
