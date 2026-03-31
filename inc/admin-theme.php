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
        'Mikroinlägg',
        'Mikroinlägg',
        'manage_options',
        'blogtree-mikroinlagg',
        'blogtree_admin_page_mikroinlagg'
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

// ── Sida: Anpassa (flikar: Bilddimensioner | Färger) ──────────────────────────
function blogtree_admin_page_anpassa(): void {
    $tab     = sanitize_key($_GET['tab'] ?? 'bilder');
    $updated = $_GET['updated'] ?? '';

    $base_url = admin_url('admin.php?page=blogtree-anpassa');
    ?>
    <div class="wrap blogtree-admin-wrap">
        <h1>Anpassa</h1>

        <nav class="nav-tab-wrapper" style="margin-bottom:20px">
            <a href="<?php echo esc_url($base_url . '&tab=bilder'); ?>"
               class="nav-tab <?php echo $tab === 'bilder' ? 'nav-tab-active' : ''; ?>">
                Bilddimensioner
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=farger'); ?>"
               class="nav-tab <?php echo $tab === 'farger' ? 'nav-tab-active' : ''; ?>">
                Färger
            </a>
        </nav>

        <?php if ($tab === 'bilder'): ?>
        <?php blogtree_admin_tab_bilder(); ?>
        <?php else: ?>
        <?php blogtree_admin_tab_farger($updated); ?>
        <?php endif; ?>

    </div>
    <?php
}

// ── Flik: Bilddimensioner ─────────────────────────────────────────────────────
function blogtree_admin_tab_bilder(): void {
    $sizes = [
        [
            'name'   => 'blogtree-card-thumb',
            'w'      => 800,
            'h'      => 450,
            'ratio'  => '16:9',
            'usage'  => 'Inläggskort på ämnessidor och startsidan',
            'note'   => 'Ladda upp via "Kortbild"-rutan i inläggsredigeraren',
        ],
        [
            'name'   => 'blogtree-topic-banner',
            'w'      => 1200,
            'h'      => 400,
            'ratio'  => '3:1',
            'usage'  => 'Bannerbild på ämnessidor',
            'note'   => 'Ladda upp via ämnesinställningarna',
        ],
        [
            'name'   => 'blogtree-mikro-banner',
            'w'      => 1200,
            'h'      => 400,
            'ratio'  => '3:1',
            'usage'  => 'Bannerbild på enskilt mikroinlägg',
            'note'   => 'Ladda upp via "Utvald bild" i mikroinläggsredigeraren',
        ],
        [
            'name'   => 'blogtree-frontpage-banner',
            'w'      => 1200,
            'h'      => 500,
            'ratio'  => '2,4:1',
            'usage'  => 'Bannerbild på startsidan',
            'note'   => 'Inställningar → Utseende → Startsida – bannerbild',
        ],
        [
            'name'   => 'blogtree-hero',
            'w'      => 1200,
            'h'      => 600,
            'ratio'  => '2:1',
            'usage'  => 'Hero-bild',
            'note'   => '',
        ],
        [
            'name'   => 'blogtree-card',
            'w'      => 600,
            'h'      => 400,
            'ratio'  => '3:2',
            'usage'  => 'Generellt inläggskort (äldre storlek)',
            'note'   => 'Ersatt av blogtree-card-thumb i de flesta vyer',
        ],
    ];
    ?>
    <div class="blogtree-card">
        <h2>Bildstorlekar</h2>
        <p style="margin-bottom:16px;color:#555">
            Ladda alltid upp bilder i minst den rekommenderade storleken.
            För skärpa på skärmar med hög upplösning (retina) kan du ladda upp dubbel storlek —
            WordPress väljer rätt storlek automatiskt.
        </p>
        <table class="widefat striped" style="table-layout:fixed">
            <thead>
                <tr>
                    <th style="width:220px">Namn</th>
                    <th style="width:120px">Storlek (px)</th>
                    <th style="width:80px">Format</th>
                    <th>Var används den</th>
                    <th>Tips</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sizes as $s): ?>
            <tr>
                <td><code><?php echo esc_html($s['name']); ?></code></td>
                <td><?php echo esc_html($s['w'] . ' × ' . $s['h']); ?></td>
                <td><?php echo esc_html($s['ratio']); ?></td>
                <td><?php echo esc_html($s['usage']); ?></td>
                <td style="color:#777;font-size:.85em"><?php echo esc_html($s['note']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:16px;color:#777;font-size:.85em">
            <strong>OBS:</strong> Om du ändrar en bildstorlek eller lägger till nya bilder,
            kör "Regenerate Thumbnails" för att uppdatera redan uppladdade bilder.
        </p>
    </div>
    <?php
}

// ── Flik: Färger ──────────────────────────────────────────────────────────────
function blogtree_admin_tab_farger(string $updated = ''): void {
    $saved  = get_option('blogtree_custom_colors', []);
    $fields = blogtree_color_fields();
    ?>
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
    <?php
}

// ── Spara mikroinläggs-hero ────────────────────────────────────────────────────
add_action('admin_post_blogtree_save_mikro_hero', function () {
    if (!current_user_can('manage_options')) wp_die('Behörighet saknas.');
    check_admin_referer('blogtree_save_mikro_hero');

    update_option('blogtree_mikro_hero', [
        'label'     => sanitize_text_field($_POST['mikro_hero_label']    ?? 'MIKROINLÄGG'),
        'title'     => sanitize_text_field($_POST['mikro_hero_title']    ?? 'Mikroinlägg'),
        'desc'      => sanitize_text_field($_POST['mikro_hero_desc']     ?? ''),
        'color'     => sanitize_hex_color($_POST['mikro_hero_color']     ?? '#2c3e50') ?: '#2c3e50',
        'gradient'  => sanitize_hex_color($_POST['mikro_hero_gradient']  ?? '') ?: '',
        'banner_id' => absint($_POST['mikro_hero_banner_id'] ?? 0),
    ]);

    wp_redirect(add_query_arg(['page' => 'blogtree-mikroinlagg', 'updated' => '1'], admin_url('admin.php')));
    exit;
});

// ── Ladda in media-uppladdare på mikroinlägg-admin-sidan ──────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'wp-oppenframtid_page_blogtree-mikroinlagg') return;
    wp_enqueue_media();
});

// ── Sida: Mikroinlägg ─────────────────────────────────────────────────────────
function blogtree_admin_page_mikroinlagg(): void {
    $saved   = get_option('blogtree_mikro_hero', []);
    $updated = $_GET['updated'] ?? '';

    $label     = $saved['label']     ?? 'MIKROINLÄGG';
    $title     = $saved['title']     ?? 'Mikroinlägg';
    $desc      = $saved['desc']      ?? '';
    $color     = $saved['color']     ?? '#2c3e50';
    $gradient  = $saved['gradient']  ?? '';
    $banner_id = (int) ($saved['banner_id'] ?? 0);
    $banner_src = $banner_id ? wp_get_attachment_image_url($banner_id, 'blogtree-mikro-banner') : '';
    ?>
    <div class="wrap blogtree-admin-wrap">
        <h1>Mikroinlägg</h1>

        <?php if ($updated === '1'): ?>
        <div class="notice notice-success is-dismissible"><p>Inställningarna har sparats.</p></div>
        <?php endif; ?>

        <div class="blogtree-card">
            <h2>Hero-sektion</h2>
            <p style="margin-bottom:20px;color:#555">
                Visas överst på mikroinläggssidan (<code>/mikroinlagg/</code>).
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('blogtree_save_mikro_hero'); ?>
                <input type="hidden" name="action" value="blogtree_save_mikro_hero">

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="mikro_hero_label">Etikett</label></th>
                        <td>
                            <input type="text" id="mikro_hero_label" name="mikro_hero_label"
                                   value="<?php echo esc_attr($label); ?>" class="regular-text">
                            <p class="description">Liten text ovanför titeln, t.ex. "MIKROINLÄGG".</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mikro_hero_title">Titel</label></th>
                        <td>
                            <input type="text" id="mikro_hero_title" name="mikro_hero_title"
                                   value="<?php echo esc_attr($title); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mikro_hero_desc">Beskrivning</label></th>
                        <td>
                            <input type="text" id="mikro_hero_desc" name="mikro_hero_desc"
                                   value="<?php echo esc_attr($desc); ?>" class="regular-text">
                            <p class="description">Valfri text under titeln. Lämna tom för att dölja.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mikro_hero_color">Primärfärg</label></th>
                        <td>
                            <input type="color" id="mikro_hero_color" name="mikro_hero_color"
                                   value="<?php echo esc_attr($color); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mikro_hero_gradient">Gradientfärg</label></th>
                        <td>
                            <input type="color" id="mikro_hero_gradient" name="mikro_hero_gradient"
                                   value="<?php echo esc_attr($gradient ?: $color); ?>">
                            <p class="description">Lämna samma som primärfärgen för enfärgad bakgrund.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Bannerbild</th>
                        <td>
                            <div style="margin-bottom:10px">
                                <img id="mikro-banner-preview"
                                     src="<?php echo esc_url($banner_src); ?>"
                                     style="max-width:400px;height:auto;border-radius:4px;display:<?php echo $banner_src ? 'block' : 'none'; ?>">
                            </div>
                            <input type="hidden" id="mikro_hero_banner_id" name="mikro_hero_banner_id"
                                   value="<?php echo esc_attr($banner_id ?: ''); ?>">
                            <button type="button" class="button" id="mikro-banner-btn">
                                <?php echo $banner_id ? 'Byt bannerbild' : 'Välj bannerbild'; ?>
                            </button>
                            <button type="button" class="button" id="mikro-banner-remove"
                                    style="margin-left:4px;<?php echo $banner_id ? '' : 'display:none;'; ?>">
                                Ta bort
                            </button>
                            <p class="description" style="margin-top:8px">
                                Visas under hero-sektionen. Rekommenderat format: 1 200 × 400 px (3:1).
                            </p>
                            <script>
                            (function($) {
                                var frame;
                                $('#mikro-banner-btn').on('click', function(e) {
                                    e.preventDefault();
                                    if (frame) { frame.open(); return; }
                                    frame = wp.media({
                                        title:    'Välj bannerbild',
                                        button:   { text: 'Använd som bannerbild' },
                                        multiple: false,
                                        library:  { type: 'image' }
                                    });
                                    frame.on('select', function() {
                                        var att = frame.state().get('selection').first().toJSON();
                                        var url = att.sizes && att.sizes['blogtree-mikro-banner']
                                            ? att.sizes['blogtree-mikro-banner'].url
                                            : att.url;
                                        $('#mikro_hero_banner_id').val(att.id);
                                        $('#mikro-banner-preview').attr('src', url).show();
                                        $('#mikro-banner-btn').text('Byt bannerbild');
                                        $('#mikro-banner-remove').show();
                                    });
                                    frame.open();
                                });
                                $('#mikro-banner-remove').on('click', function(e) {
                                    e.preventDefault();
                                    $('#mikro_hero_banner_id').val('');
                                    $('#mikro-banner-preview').attr('src', '').hide();
                                    $('#mikro-banner-btn').text('Välj bannerbild');
                                    $(this).hide();
                                });
                            })(jQuery);
                            </script>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Spara inställningar'); ?>
            </form>
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
        wp_redirect(add_query_arg(['page' => 'blogtree-anpassa', 'tab' => 'farger', 'updated' => 'reset'], admin_url('admin.php')));
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
    wp_redirect(add_query_arg(['page' => 'blogtree-anpassa', 'tab' => 'farger', 'updated' => '1'], admin_url('admin.php')));
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

