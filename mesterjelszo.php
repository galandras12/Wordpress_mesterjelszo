<?php
/**
 * Plugin Name: Mesterjelszó
 * Plugin URI: https://github.com/galandras12/Wordpress_mesterjelszo
 * Description: Teljes weboldal-védelem egyetlen mesterjelszóval: oldalak, bejegyzések, egyedi tartalomtípusok, a REST API és a bejelentkezési felület zárolása, modern, testreszabható admin panel, szelektív REST API engedélyezés, "Jegyezz meg" funkció.
 * Version: 1.0.1
 * Requires at least: 6.4
 * Tested up to: 7.0
 * Requires PHP: 8.0
 * Author: Mesterjelszó
 * Author URI: https://github.com/galandras12/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mesterjelszo
 * Domain Path: /languages
 *
 * @package Mesterjelszo
 */

// Ha valaki közvetlenül próbálja meghívni a fájlt, ne engedjük.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plugin verziószám - cache-buster a beágyazott CSS/JS fájlokhoz. */
define( 'MESTERJELSZO_VERSION', '1.0.1' );

/** A plugin fő fájljának abszolút elérési útja. */
define( 'MESTERJELSZO_PLUGIN_FILE', __FILE__ );

/** A plugin könyvtárának abszolút elérési útja (záró perjellel). */
define( 'MESTERJELSZO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/** A plugin könyvtárának nyilvános URL-je (záró perjellel). */
define( 'MESTERJELSZO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** A fő beállítás-tömb tárolására szolgáló wp_options kulcs. */
define( 'MESTERJELSZO_OPTION_KEY', 'mesterjelszo_settings' );

/** A mesterjelszó hash-elt formájának tárolására szolgáló wp_options kulcs. */
define( 'MESTERJELSZO_PASSWORD_OPTION_KEY', 'mesterjelszo_password_hash' );

/** A látogatói munkamenetet azonosító süti neve. */
define( 'MESTERJELSZO_COOKIE_NAME', 'mesterjelszo_session' );

/**
 * PHP verzió-ellenőrzés. Ha a szerver nem felel meg a minimum
 * követelménynek, a plugin nem töltődik be, csak egy admin figyelmeztetést
 * jelenít meg, elkerülve ezzel a végzetes hibát (fatal error).
 */
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'A Mesterjelszó bővítmény működéséhez legalább PHP 8.0 verzió szükséges. Kérjük, egyeztess a tárhelyszolgáltatóddal a PHP verzió frissítéséről.', 'mesterjelszo' ) .
				'</p></div>';
		}
	);
	return;
}

// A plugin osztályainak betöltése.
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo-security.php';
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo-admin.php';
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo-public.php';
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo.php';
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo-activator.php';
require_once MESTERJELSZO_PLUGIN_DIR . 'includes/class-mesterjelszo-deactivator.php';

/**
 * Aktiváláskor lefutó folyamat regisztrálása (alapértelmezett beállítások
 * létrehozása, átírási szabályok frissítése).
 */
register_activation_hook( __FILE__, array( 'Mesterjelszo_Activator', 'activate' ) );

/**
 * Deaktiváláskor lefutó folyamat regisztrálása. A beállításokat szándékosan
 * megőrizzük deaktiváláskor - a végleges törlés csak eltávolításkor
 * (uninstall.php) történik meg.
 */
register_deactivation_hook( __FILE__, array( 'Mesterjelszo_Deactivator', 'deactivate' ) );

/**
 * A plugin elindítása: a fő összekötő osztály példányosítása és lefuttatása.
 *
 * @return void
 */
function mesterjelszo_run() {
	$plugin = new Mesterjelszo();
	$plugin->run();
}
mesterjelszo_run();
