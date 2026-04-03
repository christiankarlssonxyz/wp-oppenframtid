<?php
/**
 * inc/terms.php – JSON-export och JSON-synk av taggar och kategorier
 *
 * Adminverktyg under Verktyg → Taggar & kategorier.
 *
 * Export:
 *   Laddar ned en JSON-fil med alla taggar och kategorier.
 *
 * Import / Synkronisering:
 *   JSON-filen är källa till sanning:
 *   – Term finns i JSON men inte i databasen  → skapas
 *   – Term finns i JSON och i databasen       → uppdateras
 *   – Term finns i databasen men inte i JSON  → tas bort
 *
 * JSON-format:
 * {
 *   "category": [
 *     { "name": "Teknik", "slug": "teknik", "description": "", "parent_slug": "" }
 *   ],
 *   "post_tag": [
 *     { "name": "Linux", "slug": "linux", "description": "", "parent_slug": "" }
 *   ]
 * }
 */

if (!defined('ABSPATH')) exit;

// ── Registrera admin-sida ─────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_management_page(
        'Taggar & kategorier',
        'Taggar & kategorier',
        'manage_options',
        'blogtree-terms',
        'blogtree_terms_page'
    );
});

// ── Export ────────────────────────────────────────────────────────────────────
add_action('admin_post_blogtree_terms_export', function () {
    if (!current_user_can('manage_options')) wp_die('Ej behörig.');
    check_admin_referer('blogtree_terms_export_action', 'blogtree_terms_export_nonce');

    $data = [];

    foreach (['category', 'post_tag'] as $taxonomy) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);

        $data[$taxonomy] = [];

        foreach ($terms as $term) {
            $parent_slug = '';
            if ($term->parent) {
                $parent = get_term($term->parent, $taxonomy);
                if ($parent && !is_wp_error($parent)) {
                    $parent_slug = $parent->slug;
                }
            }

            $data[$taxonomy][] = [
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'parent_slug' => $parent_slug,
            ];
        }
    }

    $json     = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filename = 'terms-' . gmdate('Y-m-d') . '.json';

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
});

// ── Import / Synkronisering ───────────────────────────────────────────────────
add_action('admin_post_blogtree_terms_import', function () {
    if (!current_user_can('manage_options')) wp_die('Ej behörig.');
    check_admin_referer('blogtree_terms_import_action', 'blogtree_terms_import_nonce');

    if (empty($_FILES['terms_file']['tmp_name'])) {
        wp_redirect(add_query_arg([
            'page'   => 'blogtree-terms',
            'result' => 'no_file',
        ], admin_url('tools.php')));
        exit;
    }

    $ext        = strtolower(pathinfo($_FILES['terms_file']['name'], PATHINFO_EXTENSION));
    $mime       = mime_content_type($_FILES['terms_file']['tmp_name']);
    $valid_json = ($ext === 'json' && in_array($mime, ['application/json', 'text/plain', 'application/octet-stream'], true));
    if (!$valid_json) {
        wp_redirect(add_query_arg([
            'page'   => 'blogtree-terms',
            'result' => 'wrong_type',
        ], admin_url('tools.php')));
        exit;
    }

    $raw = file_get_contents($_FILES['terms_file']['tmp_name']);
    $json = json_decode($raw, true);

    if (!is_array($json)) {
        wp_redirect(add_query_arg([
            'page'   => 'blogtree-terms',
            'result' => 'invalid_json',
        ], admin_url('tools.php')));
        exit;
    }

    $results = blogtree_sync_terms($json);

    set_transient('blogtree_terms_results_' . get_current_user_id(), $results, 60);

    wp_redirect(add_query_arg([
        'page'   => 'blogtree-terms',
        'result' => 'done',
    ], admin_url('tools.php')));
    exit;
});

// ── Synkronisera termer ───────────────────────────────────────────────────────
function blogtree_sync_terms(array $json): array {
    $results = [
        'created' => [],
        'updated' => [],
        'deleted' => [],
        'errors'  => [],
    ];

    $supported = ['category', 'post_tag'];

    foreach ($supported as $taxonomy) {
        $incoming = $json[$taxonomy] ?? [];

        // Samla in sluggar från JSON
        $json_slugs = array_filter(array_map(
            fn($t) => sanitize_title($t['slug'] ?? ''),
            $incoming
        ));

        // Hämta alla befintliga termer i databasen
        $existing_terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);
        $existing_by_slug = [];
        foreach ($existing_terms as $t) {
            $existing_by_slug[$t->slug] = $t;
        }

        // ── Skapa eller uppdatera termer från JSON ──────────────────────────
        // Föräldrar måste skapas innan barn – sortera efter om parent_slug finns
        usort($incoming, fn($a, $b) => empty($a['parent_slug']) ? -1 : 1);

        foreach ($incoming as $item) {
            $name        = sanitize_text_field($item['name']        ?? '');
            $slug        = sanitize_title($item['slug']             ?? '');
            $description = sanitize_textarea_field($item['description'] ?? '');
            $parent_slug = sanitize_title($item['parent_slug']      ?? '');

            if (!$name || !$slug) {
                $results['errors'][] = "$taxonomy: name och slug krävs, hoppas över.";
                continue;
            }

            // Hitta förälder-ID
            $parent_id = 0;
            if ($parent_slug) {
                $parent_term = get_term_by('slug', $parent_slug, $taxonomy);
                if ($parent_term) {
                    $parent_id = $parent_term->term_id;
                }
            }

            $term_data = [
                'description' => $description,
                'slug'        => $slug,
                'parent'      => $parent_id,
            ];

            if (isset($existing_by_slug[$slug])) {
                // Uppdatera befintlig
                $result = wp_update_term($existing_by_slug[$slug]->term_id, $taxonomy, array_merge($term_data, ['name' => $name]));
                if (is_wp_error($result)) {
                    $results['errors'][] = "$taxonomy \"$name\": " . $result->get_error_message();
                } else {
                    $results['updated'][] = "$taxonomy: $name";
                }
            } else {
                // Skapa ny
                $result = wp_insert_term($name, $taxonomy, $term_data);
                if (is_wp_error($result)) {
                    $results['errors'][] = "$taxonomy \"$name\": " . $result->get_error_message();
                } else {
                    $results['created'][] = "$taxonomy: $name";
                }
            }
        }

        // ── Ta bort termer som inte finns i JSON ───────────────────────────
        foreach ($existing_by_slug as $slug => $term) {
            if (!in_array($slug, $json_slugs, true)) {
                $del = wp_delete_term($term->term_id, $taxonomy);
                if (is_wp_error($del)) {
                    $results['errors'][] = "$taxonomy \"$term->name\" (borttagning): " . $del->get_error_message();
                } else {
                    $results['deleted'][] = "$taxonomy: $term->name";
                }
            }
        }
    }

    return $results;
}

// ── Admin-sida ────────────────────────────────────────────────────────────────
function blogtree_terms_page(): void {
    if (!current_user_can('manage_options')) return;

    $result  = $_GET['result'] ?? '';
    $results = get_transient('blogtree_terms_results_' . get_current_user_id());
    if ($results) {
        delete_transient('blogtree_terms_results_' . get_current_user_id());
    }
    ?>
    <div class="wrap">
        <h1>Taggar &amp; kategorier – export / import</h1>

        <?php if ($result === 'no_file'): ?>
        <div class="notice notice-error"><p>Ingen fil valdes.</p></div>

        <?php elseif ($result === 'wrong_type'): ?>
        <div class="notice notice-error"><p>Fel filtyp – endast JSON-filer (.json) accepteras.</p></div>

        <?php elseif ($result === 'invalid_json'): ?>
        <div class="notice notice-error"><p>Filen innehåller ogiltig JSON.</p></div>

        <?php elseif ($result === 'done' && $results): ?>
        <div class="notice notice-success">
            <p>
                <strong>Synkronisering klar.</strong>
                Skapade: <?php echo count($results['created']); ?> &nbsp;|&nbsp;
                Uppdaterade: <?php echo count($results['updated']); ?> &nbsp;|&nbsp;
                Borttagna: <?php echo count($results['deleted']); ?> &nbsp;|&nbsp;
                Fel: <?php echo count($results['errors']); ?>
            </p>
        </div>

        <?php foreach ([
            'created' => 'Skapade',
            'updated' => 'Uppdaterade',
            'deleted' => 'Borttagna',
            'errors'  => 'Fel',
        ] as $key => $label):
            if (!empty($results[$key])): ?>
        <h3><?php echo $label; ?> (<?php echo count($results[$key]); ?>)</h3>
        <ul <?php if ($key === 'errors') echo 'style="color:#c0392b"'; ?>>
            <?php foreach ($results[$key] as $item): ?>
            <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; endforeach; ?>

        <?php endif; ?>

        <!-- Export -->
        <h2>Exportera</h2>
        <p>Laddar ned en JSON-fil med alla befintliga taggar och kategorier.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="blogtree_terms_export">
            <?php wp_nonce_field('blogtree_terms_export_action', 'blogtree_terms_export_nonce'); ?>
            <?php submit_button('Exportera till JSON', 'secondary'); ?>
        </form>

        <hr>

        <!-- Import -->
        <h2>Importera / synkronisera</h2>
        <div class="notice notice-warning inline">
            <p>
                <strong>OBS:</strong> JSON-filen är källa till sanning.
                Taggar och kategorier som <em>saknas</em> i filen men <em>finns</em> i databasen
                kommer att <strong>tas bort permanent</strong>.
            </p>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="margin-top:1rem">
            <input type="hidden" name="action" value="blogtree_terms_import">
            <?php wp_nonce_field('blogtree_terms_import_action', 'blogtree_terms_import_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="terms_file">JSON-fil</label></th>
                    <td>
                        <input type="file" id="terms_file" name="terms_file" accept=".json" required>
                        <p class="description">Använd en fil exporterad från det här verktyget, eller bygg en manuellt.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Synkronisera från JSON'); ?>
        </form>

        <hr>

        <!-- Format -->
        <h2>JSON-format</h2>
        <pre style="background:#f6f7f7;padding:12px;border:1px solid #ddd;max-width:700px;overflow-x:auto">{
  "category": [
    { "name": "Teknik",  "slug": "teknik",  "description": "", "parent_slug": "" },
    { "name": "Linux",   "slug": "linux",   "description": "", "parent_slug": "teknik" }
  ],
  "post_tag": [
    { "name": "Open Source", "slug": "open-source", "description": "", "parent_slug": "" }
  ]
}</pre>
        <table class="widefat striped" style="max-width:700px;margin-top:1rem">
            <thead>
                <tr><th>Fält</th><th>Obligatoriskt</th><th>Beskrivning</th></tr>
            </thead>
            <tbody>
                <tr><td><code>name</code></td>        <td>Ja</td>  <td>Visningsnamn</td></tr>
                <tr><td><code>slug</code></td>         <td>Ja</td>  <td>URL-slug, används som nyckel</td></tr>
                <tr><td><code>description</code></td> <td>Nej</td> <td>Beskrivning</td></tr>
                <tr><td><code>parent_slug</code></td> <td>Nej</td> <td>Slug för förälderkategori (lämna tomt om ingen)</td></tr>
            </tbody>
        </table>
    </div>
    <?php
}
