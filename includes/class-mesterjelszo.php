<?php
/**
 * A plugin fő, összekötő osztálya.
 *
 * @package Mesterjelszo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mesterjelszo
 *
 * Példányosítja és összeköti az admin és publikus oldali alosztályokat,
 * valamint gondoskodik a fordítási fájlok betöltéséről.
 */
class Mesterjelszo {

	/**
	 * Az admin oldali logikát megvalósító osztály példánya.
	 *
	 * @var Mesterjelszo_Admin
	 */
	protected $admin;

	/**
	 * A publikus oldali logikát megvalósító osztály példánya.
	 *
	 * @var Mesterjelszo_Public
	 */
	protected $public;

	/**
	 * A biztonsági osztály (jelszó, munkamenet, brute-force védelem) példánya.
	 *
	 * @var Mesterjelszo_Security
	 */
	protected $security;

	/**
	 * Konstruktor: az alosztályok példányosítása.
	 */
	public function __construct() {
		$this->security = new Mesterjelszo_Security();
		$this->admin    = new Mesterjelszo_Admin( $this->security );
		$this->public   = new Mesterjelszo_Public( $this->security );
	}

	/**
	 * Minden szükséges hook regisztrálása és a plugin elindítása.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Az admin osztály hookjait csak a wp-admin területen regisztráljuk,
		// a publikus osztályét viszont mindig, hiszen a bejelentkezési
		// felület és az AJAX végpontok zárolásához ez elengedhetetlen.
		if ( is_admin() ) {
			$this->admin->init();
		}

		$this->public->init();
	}

	/**
	 * A plugin fordítási (.mo/.po) fájljainak betöltése.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'mesterjelszo',
			false,
			dirname( plugin_basename( MESTERJELSZO_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
