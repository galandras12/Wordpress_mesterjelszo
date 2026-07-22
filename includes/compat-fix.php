<?php
/**
 * Mesterjelszo - Kompatibilitási réteg 1.0.2
 * Nem töröl semmit, csak kizárja a kritikus kéréseket a kapu alól
 * hogy ne okozzon 503-at Ultimate Member, LoginPress, Jetpack, Wordfence mellett
 */
if (!defined('ABSPATH')) exit;

function mesterjelszo_is_excluded_request() {
    // Mindig engedjük az AJAX-t és CRON-t
    if (defined('DOING_AJAX') && DOING_AJAX) return true;
    if (defined('DOING_CRON') && DOING_CRON) return true;
    if (defined('REST_REQUEST') && REST_REQUEST) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Csak a problémás REST endpointokat engedjük, a többit védjük ha kell
        if (strpos($uri, '/wp-json/jetpack/') !== false) return true;
        if (strpos($uri, '/wp-json/wordfence/') !== false) return true;
        if (strpos($uri, '/wp-json/um/') !== false) return true;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $pagenow = $GLOBALS['pagenow'] ?? '';

    // Alap WordPress belépési pontok - ezeket SOHA nem zárjuk
    if ($pagenow === 'wp-login.php') return true;
    if (strpos($uri, 'wp-login.php') !== false) return true;
    if (strpos($uri, 'admin-ajax.php') !== false) return true;
    if (strpos($uri, 'wp-cron.php') !== false) return true;
    if (strpos($uri, 'wp-activate.php') !== false) return true;

    // AJAX action-ök amik 503-at kaptak eddig
    if (isset($_REQUEST['action'])) {
        $allowed_actions = [
            'um_login', 'um_register', 'um_reset_password',
            'login_with_ajax', 'lwa_login', 
            'loginpress_login', 
            'bit_smtp', 'bit_smtp_test',
            'big_file_upload', 'heartbeat'
        ];
        if (in_array($_REQUEST['action'], $allowed_actions, true)) return true;
    }

    // Ultimate Member oldalak
    if (class_exists('UM') || function_exists('UM')) {
        // Ha az URL-ben van /login, /account, /password-reset akkor engedjük
        if (preg_match('#/(login|account|password-reset|register)/?#i', $uri)) return true;
    }

    // LoginPress preview
    if (strpos($uri, 'loginpress') !== false) return true;

    // Filter hogy bővíthető legyen
    return apply_filters('mesterjelszo_exclude_current_request', false);
}
