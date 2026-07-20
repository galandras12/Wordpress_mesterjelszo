<?php
/**
 * A bővítmény aktiválásakor lefutó folyamat.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo_Activator
 */
class Mesterjelszo_Activator {

	/**
	 * Aktiváláskor lefutó teendők:
	 * - alapértelmezett beállítások létrehozása, ha még nem léteznek;
	 * - üres jelszó-option létrehozása, ha még nem létezik (a mesterjelszót
	 *   az adminnak explicit módon be kell állítania, alapértelmezett
	 *   jelszót biztonsági okokból szándékosan nem generálunk automatikusan);
	 * - átírási szabályok frissítése.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( false === get_option( MESTERJELSZO_OPTION_KEY ) ) {
			add_option( MESTERJELSZO_OPTION_KEY, Mesterjelszo_Admin::get_default_settings() );
		}

		if ( false === get_option( MESTERJELSZO_PASSWORD_OPTION_KEY ) ) {
			add_option( MESTERJELSZO_PASSWORD_OPTION_KEY, '' );
		}

		flush_rewrite_rules();
	}
}
