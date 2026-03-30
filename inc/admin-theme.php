<?php
/**
 * inc/admin-theme.php – Adminmeny för WP ÖppenFramtid
 *
 * - Toppnivå-menyval "WP ÖppenFramtid"
 * - Undermeny: Om, Anpassa, Färger
 */

// ── Registrera adminmenyn ──────────────────────────────────────────────────────
add_action('admin_menu', function () {

    add_menu_page(
        'WP ÖppenFramtid',
        'WP ÖppenFramtid',
        'manage_options',
        'blogtree-theme',
        'blogtree_admin_page_om',
        'dashicons-layout',
        3
    );

    add_submenu_page(
        'blogtree-theme',
        'Om temat',
        'Om',
        'manage_options',
        'blogtree-theme',
        'blogtree_admin_page_om'
    );

    add_submenu_page(
        'blogtree-theme',
        'Anpassa',
        'Anpassa',
        'manage_options',
        'blogtree-anpassa',
        'blogtree_admin_page_anpassa'
    );

    add_submenu_page(
        'blogtree-theme',
        'Färger',
        'Färger',
        'manage_options',
        'blogtree-farger',
        'blogtree_admin_page_farger'
    );
});

// ── Admin-CSS ──────────────────────────────────────────────────────────────────
add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'blogtree') === false) return;
    ?>
    <style>
    .blogtree-admin-wrap { max-width: 900px; }
    .blogtree-admin-wrap h1 { margin-bottom: 20px; }
    .blogtree-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px 28px;
        margin-bottom: 24px;
    }
    .blogtree-card h2 { font-size: 1.1rem; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .blogtree-colors-table { width: 100%; border-collapse: collapse; }
    .blogtree-colors-table th { text-align: left; padding: 8px 12px; background: #f6f7f7; border-bottom: 2px solid #e0e0e0; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
    .blogtree-colors-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .blogtree-colors-table tr:last-child td { border-bottom: none; }
    .blogtree-colors-table input[type="color"] { width: 44px; height: 32px; border: 1px solid #ccc; border-radius: 4px; padding: 2px; cursor: pointer; }
    .blogtree-color-var { font-family: monospace; font-size: .85rem; color: #555; }
    .blogtree-color-reset { font-size: .8rem; color: #888; cursor: pointer; text-decoration: underline; background: none; border: none; padding: 0; }
    .blogtree-color-reset:hover { color: #d63638; }
    .blogtree-section-title { font-size: 1rem; font-weight: 600; margin: 24px 0 12px; color: #1d2327; }
    .blogtree-hardcoded-table { width: 100%; border-collapse: collapse; }
    .blogtree-hardcoded-table th { text-align: left; padding: 8px 12px; background: #f6f7f7; border-bottom: 2px solid #e0e0e0; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
    .blogtree-hardcoded-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: .9rem; }
    .blogtree-hardcoded-table tr:last-child td { border-bottom: none; }
    .blogtree-swatch { display: inline-block; width: 20px; height: 20px; border-radius: 4px; border: 1px solid rgba(0,0,0,.15); vertical-align: middle; margin-right: 8px; }
    .blogtree-var-badge { display: inline-block; background: #e8f0fe; color: #1a56db; font-family: monospace; font-size: .8rem; padding: 2px 8px; border-radius: 4px; }
    </style>
    <?php
});

// ── Sida: Om ───────────────────────────────────────────────────────────────────
function blogtree_admin_page_om(): void {
    $theme = wp_get_theme();
    ?>
    <div class="wrap blogtree-admin-wrap">
        <h1><?php echo esc_html($theme->get('Name')); ?></h1>
        <div class="blogtree-card">
            <table class="form-table" role="presentation">
                <tr>
                    <th>Temats namn</th>
                    <td><?php echo esc_html($theme->get('Name')); ?></td>
                </tr>
                <tr>
                    <th>Version</th>
                    <td><?php echo esc_html($theme->get('Version')); ?></td>
                </tr>
                <tr>
                    <th>Beskrivning</th>
                    <td><?php echo esc_html($theme->get('Description')); ?></td>
                </tr>
                <tr>
                    <th>Författare</th>
                    <td><?php echo esc_html($theme->get('Author')); ?></td>
                </tr>
                <tr>
                    <th>Text-domän</th>
                    <td><code><?php echo esc_html($theme->get('TextDomain')); ?></code></td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

// ── Sida: Anpassa ──────────────────────────────────────────────────────────────
function blogtree_admin_page_anpassa(): void {
    ?>
    <div class="wrap blogtree-admin-wrap">
        <h1>Anpassa</h1>
        <div class="blogtree-card">
            <p>Här samlas temats inställningar. Fler alternativ tillkommer.</p>
        </div>
    </div>
    <?php
}

// ── Spara färginställningar ────────────────────────────────────────────────────
add_action('admin_post_blogtree_save_colors', function () {
    if (!current_user_can('manage_options')) wp_die('Behörighet saknas.');
    check_admin_referer('blogtree_save_colors');

    if (isset($_POST['reset'])) {
        delete_option('blogtree_custom_colors');
        wp_redirect(add_query_arg(['page' => 'blogtree-farger', 'updated' => 'reset'], admin_url('admin.php')));
        exit;
    }

    $colors = blogtree_color_fields();
    $saved  = [];

    foreach ($colors as $mode => $fields) {
        foreach ($fields as $key => $_) {
            $field = "color_{$mode}_{$key}";
            if (isset($_POST[$field]) && preg_match('/^#[0-9a-fA-F]{6}$/', $_POST[$field])) {
                $saved[$mode][$key] = sanitize_hex_color($_POST[$field]);
            }
        }
    }

    update_option('blogtree_custom_colors', $saved);
    wp_redirect(add_query_arg(['page' => 'blogtree-farger', 'updated' => '1'], admin_url('admin.php')));
    exit;
});

// ── Mata ut anpassade färger i <head> ──────────────────────────────────────────
add_action('wp_head', function () {
    $saved = get_option('blogtree_custom_colors', []);
    if (empty($saved)) return;

    $light = $saved['light'] ?? [];
    $dark  = $saved['dark']  ?? [];

    $css = '';

    if ($light) {
        $css .= ':root{';
        foreach ($light as $key => $val) {
            $css .= '--' . esc_attr($key) . ':' . esc_attr($val) . ';';
        }
        $css .= '}';
    }

    if ($dark) {
        $css .= '[data-theme="dark"]{';
        foreach ($dark as $key => $val) {
            $css .= '--' . esc_attr($key) . ':' . esc_attr($val) . ';';
        }
        $css .= '}';
    }

    echo '<style id="blogtree-custom-colors">' . $css . '</style>' . "\n";
}, 20);

// ── Hjälp: färgfältsdefinitioner ──────────────────────────────────────────────
function blogtree_color_fields(): array {
    return [
        'light' => [
            'color-bg'           => ['label' => 'Bakgrund',          'desc' => 'Sidans bakgrundsfärg',          'default' => '#ffffff'],
            'color-surface'      => ['label' => 'Yta',               'desc' => 'Kort- och boxbakgrund',         'default' => '#f8f8f8'],
            'color-border'       => ['label' => 'Kantlinje',         'desc' => 'Ramar och separatorer',         'default' => '#e5e5e5'],
            'color-text'         => ['label' => 'Text',              'desc' => 'Brödtext',                      'default' => '#1a1a1a'],
            'color-muted'        => ['label' => 'Dämpad text',       'desc' => 'Datum, metadata',               'default' => '#6b7280'],
            'color-accent'       => ['label' => 'Accent',            'desc' => 'Primärknapp, rubriker',         'default' => '#2c3e50'],
            'color-accent-alt'   => ['label' => 'Sekundär accent',   'desc' => 'Länkar, sekundärknapp',         'default' => '#3498db'],
            'color-danger'       => ['label' => 'Fara',              'desc' => 'Radera, varningar',             'default' => '#e74c3c'],
            'color-danger-dark'  => ['label' => 'Fara (mörk)',       'desc' => 'Hover/djupare farafärg',        'default' => '#c0392b'],
            'color-success'      => ['label' => 'Framgång',          'desc' => 'Bekräftelser, badges',          'default' => '#27ae60'],
            'color-success-dark' => ['label' => 'Framgång (mörk)',   'desc' => 'Hover/djupare framgångsfärg',   'default' => '#1e8449'],
            'color-warning'      => ['label' => 'Varning',           'desc' => 'Varningsstatus',                'default' => '#e67e22'],
            'color-btn-text'     => ['label' => 'Knapptext',         'desc' => 'Text på fyllda knappar',        'default' => '#ffffff'],
            'color-role-admin'   => ['label' => 'Roll: Admin',       'desc' => 'Admin-rollbadge',               'default' => '#8e44ad'],
            'color-role-mod'     => ['label' => 'Roll: Moderator',   'desc' => 'Moderator-rollbadge',           'default' => '#2980b9'],
        ],
        'dark' => [
            'color-bg'           => ['label' => 'Bakgrund',          'desc' => 'Sidans bakgrundsfärg',          'default' => '#0f1117'],
            'color-surface'      => ['label' => 'Yta',               'desc' => 'Kort- och boxbakgrund',         'default' => '#1a1d27'],
            'color-border'       => ['label' => 'Kantlinje',         'desc' => 'Ramar och separatorer',         'default' => '#2a2d3a'],
            'color-text'         => ['label' => 'Text',              'desc' => 'Brödtext',                      'default' => '#e8eaf0'],
            'color-muted'        => ['label' => 'Dämpad text',       'desc' => 'Datum, metadata',               'default' => '#9399a8'],
            'color-accent'       => ['label' => 'Accent',            'desc' => 'Primärknapp, rubriker',         'default' => '#a8b4c8'],
            'color-accent-alt'   => ['label' => 'Sekundär accent',   'desc' => 'Länkar, sekundärknapp',         'default' => '#5ba3e8'],
            'color-btn-text-dark'=> ['label' => 'Knapptext (mörkt)', 'desc' => 'Text på primärknappar i mörkt läge', 'default' => '#4a4f5a'],
        ],
    ];
}

// ── Sida: Färger ───────────────────────────────────────────────────────────────
function blogtree_admin_page_farger(): void {
    $saved  = get_option('blogtree_custom_colors', []);
    $fields = blogtree_color_fields();

    $updated = $_GET['updated'] ?? '';
    ?>
    <div class="wrap blogtree-admin-wrap">
        <h1>Färger</h1>

        <?php if ($updated === '1'): ?>
        <div class="notice notice-success is-dismissible"><p>Färginställningarna har sparats.</p></div>
        <?php elseif ($updated === 'reset'): ?>
        <div class="notice notice-success is-dismissible"><p>Färgerna har återställts till temats standardvärden.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('blogtree_save_colors'); ?>
            <input type="hidden" name="action" value="blogtree_save_colors">

            <!-- Ljust läge -->
            <div class="blogtree-card">
                <h2>Ljust läge</h2>
                <table class="blogtree-colors-table">
                    <thead>
                        <tr>
                            <th>Variabel</th>
                            <th>Vad den styr</th>
                            <th>Färg</th>
                            <th>Standard</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fields['light'] as $key => $f):
                        $current = $saved['light'][$key] ?? $f['default'];
                    ?>
                    <tr>
                        <td>
                            <span class="blogtree-color-var">--<?php echo esc_html($key); ?></span><br>
                            <strong><?php echo esc_html($f['label']); ?></strong>
                        </td>
                        <td><?php echo esc_html($f['desc']); ?></td>
                        <td>
                            <input type="color"
                                   name="color_light_<?php echo esc_attr($key); ?>"
                                   value="<?php echo esc_attr($current); ?>">
                        </td>
                        <td>
                            <span class="blogtree-swatch" style="background:<?php echo esc_attr($f['default']); ?>"></span>
                            <code><?php echo esc_html($f['default']); ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mörkt läge -->
            <div class="blogtree-card">
                <h2>Mörkt läge</h2>
                <table class="blogtree-colors-table">
                    <thead>
                        <tr>
                            <th>Variabel</th>
                            <th>Vad den styr</th>
                            <th>Färg</th>
                            <th>Standard</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fields['dark'] as $key => $f):
                        $current = $saved['dark'][$key] ?? $f['default'];
                    ?>
                    <tr>
                        <td>
                            <span class="blogtree-color-var">--<?php echo esc_html($key); ?></span><br>
                            <strong><?php echo esc_html($f['label']); ?></strong>
                        </td>
                        <td><?php echo esc_html($f['desc']); ?></td>
                        <td>
                            <input type="color"
                                   name="color_dark_<?php echo esc_attr($key); ?>"
                                   value="<?php echo esc_attr($current); ?>">
                        </td>
                        <td>
                            <span class="blogtree-swatch" style="background:<?php echo esc_attr($f['default']); ?>"></span>
                            <code><?php echo esc_html($f['default']); ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p>
                <?php submit_button('Spara färger', 'primary', 'submit', false); ?>
                &nbsp;
                <button type="submit" name="reset" value="1"
                        class="button button-secondary"
                        onclick="return confirm('Återställa alla färger till temats standardvärden?')">
                    Återställ till standard
                </button>
            </p>
        </form>
    </div>
    <?php
}
