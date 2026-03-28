<?php
/**
 * inc/maintenance.php – Underhållsläge
 *
 * Aktiveras via Admin → Underhållsläge.
 * Visar en 503-sida för besökare.
 * Administratörer ser sajten som vanligt.
 * Inloggningssidan är alltid tillgänglig.
 *
 * Två lägen:
 * - Underhåll (🔧): tekniskt underhåll
 * - Ombyggnad (🏗️): sajten byggs om
 */

// ── Admin-meny ─────────────────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_menu_page(
        'Underhållsläge',
        'Underhållsläge',
        'manage_options',
        'blogtree-maintenance',
        'blogtree_maintenance_page',
        'dashicons-hammer',
        3
    );
});

function blogtree_maintenance_page(): void {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['_nonce']) && wp_verify_nonce($_POST['_nonce'], 'blogtree_maintenance_save')) {
        update_option('blogtree_maintenance', [
            'enabled'   => !empty($_POST['enabled']),
            'mode'      => sanitize_key($_POST['mode'] ?? 'maintenance'),
            'message'   => sanitize_textarea_field($_POST['message'] ?? ''),
            'link'      => esc_url_raw($_POST['link'] ?? ''),
            'link_text' => sanitize_text_field($_POST['link_text'] ?? ''),
        ]);
        echo '<div class="notice notice-success"><p>Sparat.</p></div>';
    }

    $opts = wp_parse_args(get_option('blogtree_maintenance', []), [
        'enabled'   => false,
        'mode'      => 'maintenance',
        'message'   => 'Vi är strax tillbaka.',
        'link'      => '',
        'link_text' => '',
    ]);
    ?>
    <div class="wrap">
        <h1>Underhållsläge</h1>
        <form method="post">
            <?php wp_nonce_field('blogtree_maintenance_save', '_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Aktivera</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="1" <?php checked($opts['enabled']); ?>>
                            Visa underhållssida för besökare
                        </label>
                        <p class="description">Administratörer ser alltid sajten normalt.</p>
                    </td>
                </tr>
                <tr>
                    <th>Läge</th>
                    <td>
                        <label style="margin-right:16px">
                            <input type="radio" name="mode" value="maintenance" <?php checked($opts['mode'], 'maintenance'); ?>>
                            🔧 Underhåll
                        </label>
                        <label>
                            <input type="radio" name="mode" value="construction" <?php checked($opts['mode'], 'construction'); ?>>
                            🏗️ Ombyggnad
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="maint-msg">Meddelande</label></th>
                    <td><textarea id="maint-msg" name="message" rows="3" class="large-text"><?php echo esc_textarea($opts['message']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="maint-link">Länk (valfri)</label></th>
                    <td><input id="maint-link" type="url" name="link" value="<?php echo esc_attr($opts['link']); ?>" class="regular-text" placeholder="https://..."></td>
                </tr>
                <tr>
                    <th><label for="maint-link-text">Länktext</label></th>
                    <td><input id="maint-link-text" type="text" name="link_text" value="<?php echo esc_attr($opts['link_text']); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button('Spara'); ?>
        </form>
    </div>
    <?php
}

// ── Visa underhållssida för besökare ──────────────────────────────────────────
add_action('template_redirect', function () {
    $opts = get_option('blogtree_maintenance', []);
    if (empty($opts['enabled']))          return;
    if (current_user_can('manage_options')) return;
    if (is_admin())                        return;

    // Inloggningssidan är alltid tillgänglig
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    foreach (['logga-in', 'registrera', 'wp-login.php'] as $slug) {
        if (strpos($path, $slug) !== false) return;
    }

    $mode     = $opts['mode']      ?? 'maintenance';
    $message  = $opts['message']   ?? 'Vi är strax tillbaka.';
    $link     = $opts['link']      ?? '';
    $linktext = $opts['link_text'] ?? '';
    $is_construction = $mode === 'construction';
    $icon  = $is_construction ? '🏗️' : '🔧';
    $title = $is_construction ? 'Ombyggnad pågår' : 'Underhåll pågår';
    $color = $is_construction ? '#e67e22' : '#2c3e50';

    http_response_code(503);
    header('Retry-After: 3600');
    ?>
    <!DOCTYPE html>
    <html lang="sv">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($title . ' – ' . get_bloginfo('name')); ?></title>
        <style>
            body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f5f5f5; font-family:system-ui,sans-serif; padding:24px; margin:0; }
            .card { background:#fff; border-radius:12px; padding:48px 40px; max-width:480px; width:100%; text-align:center; box-shadow:0 4px 24px rgba(0,0,0,.08); }
            .icon { font-size:3rem; margin-bottom:12px; }
            h1 { font-size:1.4rem; margin:0 0 12px; }
            p { color:#555; line-height:1.7; white-space:pre-line; }
            a.btn { display:inline-block; margin-top:20px; padding:12px 28px; background:<?php echo esc_attr($color); ?>; color:#fff; border-radius:6px; text-decoration:none; font-weight:600; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon"><?php echo $icon; ?></div>
            <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
            <p><?php echo esc_html($message); ?></p>
            <?php if ($link): ?>
            <a href="<?php echo esc_url($link); ?>" class="btn"><?php echo esc_html($linktext ?: $link); ?></a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
});
