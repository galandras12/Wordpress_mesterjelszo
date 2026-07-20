<?php
/**
 * A bővítmény deaktiválásakor lefutó folyamat.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Deactivator
 */
class Mesterjelszo_Deactivator {

	/**
	 * Deaktiváláskor lefutó teendők. A beállításokat és a mesterjelszót
	 * SZÁNDÉKOSAN megőrizzük, hogy egy esetleges újraaktiváláskor ne
	 * vesszen el a konfiguráció. A végleges adattörlés kizárólag a plugin
	 * teljes eltávolításakor (uninstall.php) történik meg.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
