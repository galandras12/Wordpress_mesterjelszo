<?php
if (!defined('ABSPATH')) exit;

class Mesterjelszo_Public {

    private $options;

    public function __construct() {
        $this->options = get_option('mesterjelszo_settings', []);
    }

    // JAVÍTOTT HOOK REGISZTRÁLÁS - ne init-en, hanem template_redirect-en
    public function run() {
        // Prioritás 1, hogy még a cache pluginek előtt fusson, de az init után
        add_action('template_redirect', [$this, 'maybe_gate'], 1);
    }

    // EZ A FŐ JAVÍTÁS
    public function maybe_gate() {
        // 1. Kompatibilitási kizárás - EZ MENTI MEG A LOGIN-T
        if (function_exists('mesterjelszo_is_excluded_request') && mesterjelszo_is_excluded_request()) {
            return;
        }

        // 2. Admin, logged-in user aki beállította hogy lássa?
        if (is_admin()) return;
        if (is_user_logged_in()) {
            // Ha be van kapcsolva hogy a bejelentkezett felhasználókat ne zárja
            if (!empty($this->options['allow_logged_in'])) return;
            // Vagy ha admin
            if (current_user_can('manage_options')) return;
        }

        // 3. Már átment a kapun? Cookie ellenőrzés
        if ($this->has_valid_cookie()) {
            return;
        }

        // 4. Itt jön az eredeti logikád - MINDEN MEGJELENÉS TESTRESZABÁSOD MEGMARAD
        // Csak akkor renderelünk ha tényleg kell
        $this->render_gate_and_exit();
    }

    private function has_valid_cookie() {
        $cookie_name = 'mesterjelszo_passed_' . COOKIEHASH;
        if (empty($_COOKIE[$cookie_name])) return false;
        
        $stored_hash = $this->options['password_hash'] ?? '';
        if (empty($stored_hash)) return false;

        // Ellenőrizzük a cookie-t
        return hash_equals($stored_hash, $_COOKIE[$cookie_name]);
    }

    private function render_gate_and_exit() {
        // FONTOS: Ne 503-at küldjünk, mert a tárhely WAF-ja és a Wordfence blokkolja
        // 200 OK + noindex kell
        status_header(200);
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow');

        // Itt jön az összes eddigi megjelenés testreszabásod
        // Ezeket az opciókat te már kezeled az adminban
        $bg = $this->options['background_image'] ?? '';
        $logo = $this->options['logo'] ?? '';
        $title = $this->options['title'] ?? 'Védett oldal';
        $message = $this->options['message'] ?? 'Kérlek add meg a mesterjelszót a folytatáshoz.';
        $button_text = $this->options['button_text'] ?? 'Belépés';
        $primary_color = $this->options['primary_color'] ?? '#2271b1';
        $bg_color = $this->options['bg_color'] ?? '#f0f0f1';
        
        // Ha van saját template-ed, azt használd
        // Ezt hagytam meg kompatibilisnek a te 1.0.1-es verzióddal
        if (file_exists(plugin_dir_path(__FILE__) . 'templates/gate-template.php')) {
            include plugin_dir_path(__FILE__) . 'templates/gate-template.php';
            exit;
        }

        // Fallback template - a te dizájnoddal, de biztosan működő
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex, nofollow">
            <title><?php echo esc_html($title); ?></title>
            <style>
                body { background: <?php echo esc_attr($bg_color); ?> <?php echo $bg ? "url('".esc_url($bg)."') center/cover no-repeat" : ''; ?>; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
                .mesterjelszo-card { background:#fff; padding:40px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.15); max-width:420px; width:90%; text-align:center; }
                .mesterjelszo-card img { max-width:180px; margin-bottom:20px; }
                .mesterjelszo-card h1 { font-size:22px; margin-bottom:10px; }
                .mesterjelszo-card p { color:#555; margin-bottom:20px; }
                .mesterjelszo-card input[type="password"] { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; margin-bottom:15px; font-size:16px; box-sizing:border-box; }
                .mesterjelszo-card button { width:100%; padding:12px; background:<?php echo esc_attr($primary_color); ?>; color:#fff; border:0; border-radius:8px; font-size:16px; cursor:pointer; }
                .mesterjelszo-card button:hover { opacity:0.9; }
                .mesterjelszo-error { color:#d63638; margin-bottom:15px; display:none; }
            </style>
        </head>
        <body>
            <div class="mesterjelszo-card">
                <?php if ($logo): ?><img src="<?php echo esc_url($logo); ?>" alt="Logo"><?php endif; ?>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($message); ?></p>
                <form method="post" id="mesterjelszo-form">
                    <?php wp_nonce_field('mesterjelszo_gate', 'mesterjelszo_nonce'); ?>
                    <div class="mesterjelszo-error" id="mesterjelszo-error">Hibás jelszó</div>
                    <input type="password" name="mesterjelszo_password" placeholder="Mesterjelszó" required autofocus>
                    <button type="submit"><?php echo esc_html($button_text); ?></button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
