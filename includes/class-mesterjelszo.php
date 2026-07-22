<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Mesterjelszo {
        protected $admin;
        protected $public;
        protected $security;

        public function __construct() {
                $this->security = new Mesterjelszo_Security();
                $this->admin    = new Mesterjelszo_Admin( $this->security );
                $this->public   = new Mesterjelszo_Public( $this->security );
        }

        public function run(): void {
                add_action( 'init', array( $this, 'load_textdomain' ) );

                // JAVÍTÁS: public init mindig fusson, de a kapu maga már template_redirect-en fut
                // így nem akad össze a Login with Ajax és társaival
                $this->public->init();

                if ( is_admin() ) {
                        $this->admin->init();
                }

                // Kompatibilitási figyelmeztetés adminban ha ütköző plugin van
                add_action( 'admin_notices', array( $this, 'compat_notice' ) );
        }

        public function compat_notice(): void {
                if ( ! current_user_can( 'manage_options' ) ) return;
                $plugins = array(
                        'ultimate-member/ultimate-member.php' => 'Ultimate Member',
                        'login-with-ajax/login-with-ajax.php' => 'Login with Ajax',
                        'LoginPress/loginpress.php' => 'LoginPress',
                        'wordfence/wordfence.php' => 'Wordfence',
                );
                $active = array();
                foreach ( $plugins as $file => $name ) {
                        if ( is_plugin_active( $file ) ) $active[] = $name;
                }
                if ( count( $active ) >= 2 ) {
                        $settings = Mesterjelszo_Admin::get_settings();
                        if ( ! empty( $settings['protect_login'] ) ) {
                                echo '<div class="notice notice-warning"><p><strong>Mesterjelszó:</strong> Észleltem hogy ' . esc_html( implode( ', ', $active ) ) . ' aktív. A Bejelentkezési felület védelme be van kapcsolva, ez 503-at okozhat. Javasolt kikapcsolni a Mesterjelszó > Beállítások alatt.</p></div>';
                        }
                }
        }

        public function load_textdomain(): void {
                load_plugin_textdomain(
                        'mesterjelszo',
                        false,
                        dirname( plugin_basename( MESTERJELSZO_PLUGIN_FILE ) ) . '/languages'
                );
        }
}
