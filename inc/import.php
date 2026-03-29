<?php
/**
 * inc/import.php – CSV-import av inlägg
 *
 * Adminverktyg under Verktyg → Importera inlägg.
 * Identifierar inlägg via slug:
 *   – Finns sluggen → uppdatera befintligt inlägg
 *   – Finns inte    → skapa nytt inlägg
 *
 * CSV-format (UTF-8, komma-separerat):
 *   title, slug, content, excerpt, date, status, topics
 *
 * topics = komma-separerade ämnes-sluggar, t.ex. "teknik,linux"
 */

if (!defined('ABSPATH')) exit;

// ── Registrera admin-sida ─────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_management_page(
        'Importera inlägg',
        'Importera inlägg',
        'manage_options',
        'blogtree-import',
        'blogtree_import_page'
    );
});

// ── Hantera filuppladdning ────────────────────────────────────────────────────
add_action('admin_post_blogtree_import', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Ej behörig.');
    }

    check_admin_referer('blogtree_import_action', 'blogtree_import_nonce');

    if (empty($_FILES['import_file']['tmp_name'])) {
        wp_redirect(add_query_arg([
            'page'    => 'blogtree-import',
            'result'  => 'no_file',
        ], admin_url('tools.php')));
        exit;
    }

    $file = $_FILES['import_file'];

    // Kontrollera filtyp
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        wp_redirect(add_query_arg([
            'page'   => 'blogtree-import',
            'result' => 'wrong_type',
        ], admin_url('tools.php')));
        exit;
    }

    $results = blogtree_process_csv($file['tmp_name']);

    set_transient('blogtree_import_results_' . get_current_user_id(), $results, 60);

    wp_redirect(add_query_arg([
        'page'   => 'blogtree-import',
        'result' => 'done',
    ], admin_url('tools.php')));
    exit;
});

// ── Behandla CSV ──────────────────────────────────────────────────────────────
function blogtree_process_csv(string $filepath): array {
    $results = ['created' => [], 'updated' => [], 'errors' => []];

    $handle = fopen($filepath, 'r');
    if (!$handle) {
        $results['errors'][] = 'Kunde inte öppna filen.';
        return $results;
    }

    // Läs rubrikrad
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        $results['errors'][] = 'Filen verkar tom eller saknar rubrikrad.';
        return $results;
    }

    // Normalisera rubriknamn (trimma, lowercase)
    $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

    $required = ['title', 'slug'];
    foreach ($required as $req) {
        if (!in_array($req, $headers, true)) {
            fclose($handle);
            $results['errors'][] = "Obligatorisk kolumn saknas: \"$req\".";
            return $results;
        }
    }

    $row_num = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        if (count($row) !== count($headers)) {
            $results['errors'][] = "Rad $row_num: Fel antal kolumner, hoppas över.";
            continue;
        }

        $data = array_combine($headers, $row);

        $title  = sanitize_text_field($data['title']  ?? '');
        $slug   = sanitize_title($data['slug']        ?? '');
        $content  = wp_kses_post($data['content']     ?? '');
        $excerpt  = sanitize_textarea_field($data['excerpt'] ?? '');
        $date_raw = sanitize_text_field($data['date'] ?? '');
        $status   = sanitize_key($data['status']      ?? 'draft');
        $topics_raw = $data['topics'] ?? '';

        if (!$title || !$slug) {
            $results['errors'][] = "Rad $row_num: title och slug krävs, hoppas över.";
            continue;
        }

        // Validera status
        $allowed_statuses = ['publish', 'draft', 'pending', 'private'];
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }

        // Validera datum
        $post_date = '';
        if ($date_raw) {
            $ts = strtotime($date_raw);
            $post_date = $ts ? date('Y-m-d H:i:s', $ts) : '';
        }

        // Bygg post-array
        $post_data = [
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => $status,
            'post_type'    => 'post',
        ];
        if ($post_date) {
            $post_data['post_date']     = $post_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($post_date);
        }

        // Kolla om inlägget redan finns (via slug)
        $existing = get_page_by_path($slug, OBJECT, 'post');

        if ($existing) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data, true);
            $action  = 'updated';
        } else {
            $post_id = wp_insert_post($post_data, true);
            $action  = 'created';
        }

        if (is_wp_error($post_id)) {
            $results['errors'][] = "Rad $row_num \"$title\": " . $post_id->get_error_message();
            continue;
        }

        // Koppla ämnen
        if ($topics_raw) {
            $topic_slugs = array_filter(array_map('trim', explode(',', $topics_raw)));
            $term_ids    = [];
            foreach ($topic_slugs as $ts) {
                $term = get_term_by('slug', sanitize_title($ts), 'topic');
                if ($term) {
                    $term_ids[] = $term->term_id;
                }
            }
            if ($term_ids) {
                wp_set_post_terms($post_id, $term_ids, 'topic');
            }
        }

        $results[$action][] = $title;
    }

    fclose($handle);
    return $results;
}

// ── Admin-sida ────────────────────────────────────────────────────────────────
function blogtree_import_page(): void {
    if (!current_user_can('manage_options')) return;

    $result  = $_GET['result'] ?? '';
    $results = get_transient('blogtree_import_results_' . get_current_user_id());
    if ($results) {
        delete_transient('blogtree_import_results_' . get_current_user_id());
    }
    ?>
    <div class="wrap">
        <h1>Importera inlägg</h1>

        <?php if ($result === 'no_file'): ?>
        <div class="notice notice-error"><p>Ingen fil valdes.</p></div>

        <?php elseif ($result === 'wrong_type'): ?>
        <div class="notice notice-error"><p>Fel filtyp – endast CSV-filer (.csv) accepteras.</p></div>

        <?php elseif ($result === 'done' && $results): ?>
        <div class="notice notice-success">
            <p>
                <strong>Import klar.</strong>
                Skapade: <?php echo count($results['created']); ?> &nbsp;|&nbsp;
                Uppdaterade: <?php echo count($results['updated']); ?> &nbsp;|&nbsp;
                Fel: <?php echo count($results['errors']); ?>
            </p>
        </div>

        <?php if ($results['created']): ?>
        <h3>Skapade (<?php echo count($results['created']); ?>)</h3>
        <ul><?php foreach ($results['created'] as $t): ?>
            <li><?php echo esc_html($t); ?></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>

        <?php if ($results['updated']): ?>
        <h3>Uppdaterade (<?php echo count($results['updated']); ?>)</h3>
        <ul><?php foreach ($results['updated'] as $t): ?>
            <li><?php echo esc_html($t); ?></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>

        <?php if ($results['errors']): ?>
        <h3>Fel (<?php echo count($results['errors']); ?>)</h3>
        <ul style="color:#c0392b"><?php foreach ($results['errors'] as $e): ?>
            <li><?php echo esc_html($e); ?></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>

        <?php endif; ?>

        <h2>Ladda upp CSV-fil</h2>
        <p>
            Identifiering sker via <strong>slug</strong>:
            finns sluggen redan skrivs inlägget över, annars skapas ett nytt.
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="blogtree_import">
            <?php wp_nonce_field('blogtree_import_action', 'blogtree_import_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="import_file">CSV-fil</label></th>
                    <td>
                        <input type="file" id="import_file" name="import_file" accept=".csv" required>
                        <p class="description">Endast .csv-filer. Teckenkodning: UTF-8.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Importera'); ?>
        </form>

        <hr>
        <h2>CSV-format</h2>
        <p>Rubrikrad <strong>måste</strong> finnas. Kolumnerna <code>title</code> och <code>slug</code> är obligatoriska.</p>

        <table class="widefat striped" style="max-width:700px">
            <thead>
                <tr>
                    <th>Kolumn</th>
                    <th>Obligatorisk</th>
                    <th>Beskrivning</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>title</code></td>   <td>Ja</td>  <td>Inläggets rubrik</td></tr>
                <tr><td><code>slug</code></td>    <td>Ja</td>  <td>URL-slug, används som nyckel vid uppdatering</td></tr>
                <tr><td><code>content</code></td> <td>Nej</td> <td>Brödtext (HTML tillåtet)</td></tr>
                <tr><td><code>excerpt</code></td> <td>Nej</td> <td>Utdrag / ingress</td></tr>
                <tr><td><code>date</code></td>    <td>Nej</td> <td>Publiceringsdatum, t.ex. <code>2024-03-15</code></td></tr>
                <tr><td><code>status</code></td>  <td>Nej</td> <td><code>publish</code>, <code>draft</code>, <code>pending</code>, <code>private</code> (standard: <code>draft</code>)</td></tr>
                <tr><td><code>topics</code></td>  <td>Nej</td> <td>Komma-separerade ämnes-sluggar, t.ex. <code>teknik,linux</code></td></tr>
            </tbody>
        </table>

        <h3>Exempelrad</h3>
        <pre style="background:#f6f7f7;padding:12px;border:1px solid #ddd;max-width:700px;overflow-x:auto">title,slug,content,excerpt,date,status,topics
"Mitt första inlägg","mitt-forsta-inlagg","&lt;p&gt;Innehåll här.&lt;/p&gt;","Kort utdrag","2024-03-15","publish","teknik,linux"</pre>
    </div>
    <?php
}
