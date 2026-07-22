<?php
/**
 * Eltávolításkor (uninstall) lefutó takarítási folyamat.
 *
 * Ez a fájl kizárólag akkor fut le, amikor a felhasználó a WordPress admin
 * felületén véglegesen törli a bővítményt (nem deaktiválja, hanem törli).
 * Ilyenkor minden, a pluginhoz tartozó adatot eltávolítunk az adatbázisból,
 * hogy ne maradjon "árva" adat - ez GDPR szempontból is fontos gyakorlat.
 *
 * @package Mesterjelszo
 */

// Biztonsági ellenőrzés: ez a fájl csak a WordPress törlési folyamatán
// keresztül futhat le, közvetlen hívás esetén azonnal leállunk.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Fő beállítások és a jelszó hash törlése.
delete_option( 'mesterjelszo_settings' );
delete_option( 'mesterjelszo_password_hash' );
delete_option( 'mesterjelszo_password_encrypted' );
delete_option( 'mesterjelszo_db_version' );
delete_option( 'mesterjelszo_log_last_prune' );

global $wpdb;

// A bejelentkezési napló egyedi adatbázistáblájának törlése.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mesterjelszo_login_log" );
// phpcs:enable

// Többsite (multisite) hálózat esetén az összes aloldalon is takarítunk.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'mesterjelszo_settings' );
		delete_option( 'mesterjelszo_password_hash' );
		delete_option( 'mesterjelszo_password_encrypted' );
		delete_option( 'mesterjelszo_db_version' );
		delete_option( 'mesterjelszo_log_last_prune' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mesterjelszo_login_log" );
		// phpcs:enable
		restore_current_blog();
	}
}

// Esetleges maradék tranzitensek (próbálkozás-számlálók, zárolások,
// munkamenetek) eltávolítása, mivel ezek egyedi, dinamikusan generált
// kulcsneveket használnak, ezért nem törölhetők egyszerű delete_option()
// hívással.
global $wpdb;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_mesterjelszo\_%' OR option_name LIKE '\_transient\_timeout\_mesterjelszo\_%'"
);
// phpcs:enable
