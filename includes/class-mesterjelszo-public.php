<?php
/**
 * A weboldal látogatói oldalát érintő logika: a jelszókérő "kapu" (gate)
 * megjelenítése, a REST API és a bejelentkezési felület zárolása.
 * 
 * 1.0.2-compat: 503 hiba javítva, kompatibilis Ultimate Member, Login Press, 
 * Login with Ajax, Jetpack, Wordfence, Bit SMTP, Big File Upload mellé.
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

        protected $security;

        public function __construct( Mesterjelszo_Security $security ) {
                $this->security = $security;
        }

        public function init(): void {
                // JAVÍTÁS: init helyett template_redirect-en futtatjuk a kaput,
                // így a wp-login.php, admin-ajax.php, wp-cron.php már eleve kizárható
                // és nem ütközik más login bővítményekkel. Az init túl korai és minden
                // belépési ponton lefutott.
                add_action( 'template_redirect', array( $this, 'maybe_gate' ), 1 );

                // REST API védelem maradhat külön szűrőn
                add_filter( 'rest_authentication_errors', array( $this, 'protect_rest_api' ), 99 );

                // AJAX végpont a jelszó ellenőrzéshez
                add_action( 'wp_ajax_mesterjelszo_verify', array( $this, 'ajax_verify_password' ) );
                add_action( 'wp_ajax_nopriv_mesterjelszo_verify', array( $this, 'ajax_verify_password' ) );
        }

        /**
         * Kompatibilitási kizárások - EZ JAVÍTJA A 503-AT
         * @return bool true ha a jelenlegi kérést NEM szabad zárolni
         */
        protected function is_excluded_request(): bool {
                // Cron, XMLRPC, CLI mindig mehet
                if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) return true;
                if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) return true;
                if ( defined( 'WP_CLI' ) && WP_CLI ) return true;
                if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                        // REST kéréseket külön kezeljük, itt ne zárjunk
                        return true;
                }

                // Admin terület soha ne legyen zárva admin számára (biztonsági háló)
                if ( is_admin() ) return true;

                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $pagenow = $GLOBALS['pagenow'] ?? '';

                // Alap WordPress rendszerfájlok
                if ( $pagenow === 'wp-login.php' ) return true;
                if ( strpos( $uri, 'wp-login.php' ) !== false ) return true;
                if ( strpos( $uri, 'wp-register.php' ) !== false ) return true;
                if ( strpos( $uri, 'admin-ajax.php' ) !== false ) {
                        // Saját AJAX-unkat is engedjük, de azt már feljebb kezeltük
                        // Minden más AJAX-ot (pl. Login with Ajax, Bit SMTP teszt) engedünk
                        // különben 503-at dobna a login oldalon
                        return true;
                }
                if ( strpos( $uri, 'wp-cron.php' ) !== false ) return true;
                if ( strpos( $uri, 'wp-json/jetpack/' ) !== false ) return true;
                if ( strpos( $uri, 'wp-json/wordfence/' ) !== false ) return true;
                if ( strpos( $uri, 'wp-json/um/' ) !== false ) return true;

                // Ultimate Member saját oldalai - ha UM aktív, ne zárjuk a login/account oldalait
                if ( class_exists( 'UM', false ) || function_exists( 'UM' ) ) {
                        // UM login, account, password-reset oldalak URI alapú kizárása
                        if ( strpos( $uri, '/login' ) !== false || strpos( $uri, '/account' ) !== false || strpos( $uri, '/password-reset' ) !== false || strpos( $uri, '/bejelentkezes' ) !== false ) {
                                // Csak akkor zárjuk ki, ha tényleg UM oldalról van szó
                                // Biztonság kedvéért ellenőrizzük hogy UM core page-e
                                if ( function_exists( 'um_get_core_page' ) ) {
                                        $login_id = um_get_core_page( 'login' );
                                        $acc_id = um_get_core_page( 'account' );
                                        $current_path = trim( parse_url( $uri, PHP_URL_PATH ), '/' );
                                        // Ha az URI tartalmazza a core oldal slugját, kizárjuk
                                        if ( $login_id && strpos( $current_path, trim( parse_url( get_permalink( $login_id ), PHP_URL_PATH ), '/' ) ) !== false ) return true;
                                        if ( $acc_id && strpos( $current_path, trim( parse_url( get_permalink( $acc_id ), PHP_URL_PATH ), '/' ) ) !== false ) return true;
                                } else {
                                        // Fallback: ha nem tudjuk pontosan, engedjük a login/account URI-kat
                                        return true;
                                }
                        }
                }

                // Login Press, Login with Ajax, Bit SMTP, Big File Upload saját AJAX actionjei
                $doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
                if ( $doing_ajax && isset( $_REQUEST['action'] ) ) {
                        $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
                        $allowed_ajax = array(
                                'mesterjelszo_verify',
                                'loginwithajax', 'loginwithajax_widget', // Login with Ajax
                                'loginpress_', // Login Press prefix
                                'um_', // Ultimate Member
                                'bit_smtp_', // Bit SMTP
                                'bafu_', 'bigfileupload', // Big File Upload
                        );
                        foreach ( $allowed_ajax as $allowed ) {
                                if ( strpos( $action, $allowed ) !== false ) return true;
                        }
                }

                // Külső filter, hogy más bővítmény is kizárhasson
                return apply_filters( 'mesterjelszo_exclude_current_request', false );
        }

        public function maybe_gate(): void {
                if ( $this->is_excluded_request() ) {
                        return;
                }

                if ( $this->is_rest_request() ) {
                        return;
                }

                $doing_ajax  = defined( 'DOING_AJAX' ) && DOING_AJAX;
                $ajax_action = ( $doing_ajax && isset( $_REQUEST['action'] ) )
                        ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) )
                        : '';

                if ( $doing_ajax && 'mesterjelszo_verify' === $ajax_action ) {
                        return;
                }

                $settings = Mesterjelszo_Admin::get_settings();

                if ( empty( $settings['enabled'] ) || ! $this->security->has_password() ) {
                        return;
                }

                if ( $this->security->is_unlocked() ) {
                        return;
                }

                // Admin bypass - bejelentkezett adminok átugorhatják
                if ( ! empty( $settings['bypass_admins'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
                        return;
                }

                // Biztonsági háló: wp-admin soha ne legyen zárva adminnak
                if ( is_admin() && current_user_can( 'manage_options' ) ) {
                        return;
                }

                // Régi beállítás: login védelem külön kapcsoló
                // Ha ki van kapcsolva (alapértelmezett 1.0.2-től), akkor a wp-login.php-t engedjük
                // Ezt már az is_excluded_request is kezeli, de itt is ellenőrizzük
                if ( empty( $settings['protect_login'] ) ) {
                        if ( $this->is_login_page_request() ) {
                                return;
                        }
                }

                // Minden más nem hitelesített AJAX-ot 403-mal utasítunk el, nem 503-mal
                if ( $doing_ajax ) {
                        wp_send_json_error(
                                array( 'message' => __( 'Hozzáférés megtagadva: a weboldal jelszóval védett.', 'mesterjelszo' ) ),
                                403
                        );
                }

                $this->render_gate_and_exit();
        }

        protected function is_login_page_request(): bool {
                $pagenow = $GLOBALS['pagenow'] ?? '';
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                return $pagenow === 'wp-login.php' || strpos( $uri, 'wp-login.php' ) !== false;
        }

        protected function is_rest_request(): bool {
                if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                        return true;
                }
                if ( empty( $_SERVER['REQUEST_URI'] ) ) {
                        return false;
                }
                $uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
                return false !== strpos( $uri, '/' . rest_get_url_prefix() . '/' );
        }

        public function protect_rest_api( $result ) {
                if ( ! empty( $result ) ) {
                        return $result;
                }

                // Kizárt kéréseket REST-nél is engedjük
                if ( $this->is_excluded_request() ) {
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

                $route = $this->get_current_rest_route();

                if ( $this->is_rest_route_exempt( $route ) ) {
                        return $result;
                }

                return new WP_Error(
                        'mesterjelszo_rest_blocked',
                        __( 'Ez a weboldal jelenleg mesterjelszóval védett.', 'mesterjelszo' ),
                        array( 'status' => 401 )
                );
        }

        protected function get_current_rest_route(): string {
                if ( ! empty( $_GET['rest_route'] ) ) {
                        $raw = sanitize_text_field( wp_unslash( $_GET['rest_route'] ) );
                        return '/' . ltrim( $raw, '/' );
                }
                if ( empty( $_SERVER['REQUEST_URI'] ) ) {
                        return '';
                }
                $uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
                $path = wp_parse_url( $uri, PHP_URL_PATH );
                if ( ! $path ) {
                        return '';
                }
                $needle = '/' . trailingslashit( rest_get_url_prefix() );
                $pos    = strpos( $path, $needle );
                if ( false === $pos ) {
                        return '';
                }
                $route = substr( $path, $pos + strlen( $needle ) );
                return '/' . ltrim( (string) $route, '/' );
        }

        protected function is_rest_route_exempt( string $route ): bool {
                if ( '' === $route ) {
                        return false;
                }
                $route = ltrim( $route, '/' );
                foreach ( $this->get_rest_exceptions() as $prefix ) {
                        if ( '' !== $prefix && 0 === strpos( $route, $prefix ) ) {
                                return true;
                        }
                }
                return false;
        }

        protected function get_rest_exceptions(): array {
                $settings = Mesterjelszo_Admin::get_settings();
                $raw      = isset( $settings['rest_api_exceptions'] ) ? (string) $settings['rest_api_exceptions'] : '';
                $lines = preg_split( '/[\r\n]+/', $raw );
                $lines = array_map( 'trim', (array) $lines );
                $lines = array_map(
                        static function ( $line ) {
                                return ltrim( $line, '/' );
                        },
                        $lines
                );
                return array_values( array_filter( $lines ) );
        }

        public function ajax_verify_password(): void {
                check_ajax_referer( 'mesterjelszo_gate_nonce', 'nonce' );

                if ( $this->security->is_locked_out() ) {
                        wp_send_json_error(
                                array(
                                        'message' => sprintf(
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
                        $remember = ! empty( $_POST['remember_me'] );
                        $this->security->create_session( $remember );
                        $redirect = isset( $_POST['redirect_to'] )
                                ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
                                : home_url( '/' );
                        wp_send_json_success( array( 'redirect' => $redirect ) );
                }

                $this->security->register_failed_attempt();
                wp_send_json_error( array( 'message' => __( 'Hibás jelszó. Kérjük, próbáld újra.', 'mesterjelszo' ) ), 403 );
        }

        /**
         * JAVÍTÁS: 503 helyett 200-as státuszt küldünk, különben a böngésző és a
         * tárhely is 503 hibát mutat és a Wordfence is blokkolhatja.
         */
        protected function render_gate_and_exit(): void {
                nocache_headers();

                if ( ! headers_sent() ) {
                        // 503 helyett 200, de noindex
                        header( 'HTTP/1.1 200 OK' );
                        header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
                        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
                }

                include MESTERJELSZO_PLUGIN_DIR . 'public/partials/gate-page.php';
                exit;
        }
}
