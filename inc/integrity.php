<?php
/**
 * inc/integrity.php – Integritets- och säkerhetsgranskning
 *
 * Stöder två JSON-format:
 *  - "checklist"  → categories[].checks[] med status pass/warn/fail/info
 *  - "findings"   → findings[] med severity critical/high/medium/low/info
 *                   (t.ex. från security-audit.json)
 *
 * Findings-format konverteras automatiskt till checklist vid uppladdning.
 * Flera filer sammanfogas till ett gemensamt granskningsdokument.
 */

// ── Ladda standarddata om ingen finns sparad ───────────────────────────────────
add_action('after_setup_theme', function () {
    if (!get_option('blogtree_audit_data')) {
        $default = get_template_directory() . '/data/audit.json';
        if (file_exists($default)) {
            update_option('blogtree_audit_data', file_get_contents($default));
        }
    }
});

// ── Formatdetektering ──────────────────────────────────────────────────────────
function blogtree_detect_audit_format(array $data): string {
    return isset($data['findings']) && is_array($data['findings']) ? 'findings' : 'checklist';
}

// ── Icon-mappning för findings-typer ──────────────────────────────────────────
function blogtree_icon_for_finding_type(string $type): string {
    $map = [
        'sql injection'              => '💉',
        'csrf'                       => '🛡',
        'xss'                        => '⚡',
        'cryptographic'              => '🔑',
        'privacy'                    => '📋',
        'gdpr'                       => '📋',
        'sensitive data'             => '🔐',
        'session'                    => '🪪',
        'race condition'             => '⏱',
        'denial of service'          => '🚫',
        'input validation'           => '✏️',
        'open redirect'              => '↪️',
        'spam'                       => '📧',
        'audit trail'                => '📝',
        'data minimization'          => '📦',
    ];
    $lower = strtolower($type);
    foreach ($map as $keyword => $icon) {
        if (str_contains($lower, $keyword)) return $icon;
    }
    return '🔒';
}

// ── Konvertera findings-format → checklist-format ─────────────────────────────
function blogtree_convert_findings_to_checklist(array $data): array {
    $severity_to_status = [
        'critical' => 'fail',
        'high'     => 'fail',
        'medium'   => 'warn',
        'low'      => 'info',
        'info'     => 'info',
    ];

    $categories_map = [];
    foreach (($data['findings'] ?? []) as $finding) {
        $type = $finding['type'] ?? 'Övrigt';
        $key  = strtolower(preg_replace('/[^a-z0-9]/i', '-', $type));
        if (!isset($categories_map[$key])) {
            $categories_map[$key] = [
                'id'     => $key,
                'name'   => $type,
                'icon'   => blogtree_icon_for_finding_type($type),
                'checks' => [],
            ];
        }
        $severity = strtolower($finding['severity'] ?? 'info');
        $file_ref = trim(($finding['file'] ?? '') . ($finding['line'] ? ':' . $finding['line'] : ''));

        $categories_map[$key]['checks'][] = [
            'id'           => strtolower($finding['id'] ?? uniqid('f-')),
            'name'         => $finding['title'] ?? ($finding['id'] ?? ''),
            'description'  => $finding['description'] ?? '',
            'status'       => $severity_to_status[$severity] ?? 'info',
            'severity'     => $severity,
            'details'      => $file_ref,
            'alternative'  => $finding['recommendation'] ?? null,
            'code_snippet' => $finding['code_snippet'] ?? null,
            'references'   => $finding['references']    ?? [],
        ];
    }

    // Räkna statuses
    $counts = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
    foreach ($categories_map as $cat) {
        foreach ($cat['checks'] as $ch) {
            $counts[$ch['status']] = ($counts[$ch['status']] ?? 0) + 1;
        }
    }
    $total = array_sum($counts);
    $score = max(0, 100 - ($counts['fail'] * 10) - ($counts['warn'] * 5));

    $audit_meta = $data['audit'] ?? [];
    return [
        'meta' => [
            'site'        => $audit_meta['plugin']  ?? '',
            'theme'       => $audit_meta['plugin']  ?? '',
            'version'     => $audit_meta['version'] ?? '',
            'audited'     => $audit_meta['date']    ?? '',
            'auditor'     => $audit_meta['auditor'] ?? '',
            'description' => 'Säkerhetsgranskning av ' . ($audit_meta['plugin'] ?? ''),
        ],
        'summary' => [
            'score'    => $score,
            'passed'   => $counts['pass'],
            'warnings' => $counts['warn'],
            'failed'   => $counts['fail'],
            'info'     => $counts['info'],
        ],
        'categories'       => array_values($categories_map),
        '_recommendations' => $data['recommendations_prioritized'] ?? null,
    ];
}

// ── Sammanfoga två checklist-dokument ─────────────────────────────────────────
function blogtree_merge_checklists(array $base, array $incoming): array {
    $existing_ids = array_column($base['categories'] ?? [], 'id');
    foreach (($incoming['categories'] ?? []) as $cat) {
        $pos = array_search($cat['id'], $existing_ids, true);
        if ($pos !== false) {
            $existing_check_ids = array_column($base['categories'][$pos]['checks'], 'id');
            foreach ($cat['checks'] as $check) {
                if (!in_array($check['id'], $existing_check_ids, true)) {
                    $base['categories'][$pos]['checks'][] = $check;
                }
            }
        } else {
            $base['categories'][] = $cat;
            $existing_ids[]       = $cat['id'];
        }
    }
    // Slå ihop _recommendations
    if (!empty($incoming['_recommendations'])) {
        $recs = $base['_recommendations'] ?? [];
        foreach ($incoming['_recommendations'] as $key => $items) {
            $recs[$key] = array_merge($recs[$key] ?? [], (array) $items);
        }
        $base['_recommendations'] = $recs;
    }
    // Räkna om summary
    $counts = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
    foreach ($base['categories'] as $cat) {
        foreach ($cat['checks'] as $ch) {
            $counts[$ch['status']] = ($counts[$ch['status']] ?? 0) + 1;
        }
    }
    $base['summary']['passed']   = $counts['pass'];
    $base['summary']['warnings'] = $counts['warn'];
    $base['summary']['failed']   = $counts['fail'];
    $base['summary']['info']     = $counts['info'];
    $base['summary']['score']    = max(0, 100 - ($counts['fail'] * 10) - ($counts['warn'] * 5));
    return $base;
}

// ── Admin-meny ─────────────────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_menu_page(
        'Integritetsgranskning',
        'Integritet',
        'manage_options',
        'blogtree-integrity',
        'blogtree_integrity_admin_page',
        'dashicons-shield',
        4
    );
});

// ── Admin-sida ─────────────────────────────────────────────────────────────────
function blogtree_integrity_admin_page(): void {
    if (!current_user_can('manage_options')) return;

    $message = '';
    $error   = '';

    // ── Ladda upp + analysera JSON-filer ──────────────────────────────────────
    if (isset($_POST['_nonce_upload']) && wp_verify_nonce($_POST['_nonce_upload'], 'blogtree_integrity_upload')) {
        $files   = $_FILES['audit_files'] ?? [];
        $merged  = null;
        $names   = [];
        $invalid = [];

        if (!empty($files['tmp_name'])) {
            foreach ($files['tmp_name'] as $i => $tmp) {
                if (empty($tmp) || $files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $content = file_get_contents($tmp);
                $decoded = json_decode($content, true);
                if ($decoded === null) {
                    $invalid[] = esc_html($files['name'][$i]);
                    continue;
                }
                // Konvertera findings-format vid behov
                if (blogtree_detect_audit_format($decoded) === 'findings') {
                    $decoded = blogtree_convert_findings_to_checklist($decoded);
                }
                $names[] = esc_html($files['name'][$i]);
                $merged  = ($merged === null) ? $decoded : blogtree_merge_checklists($merged, $decoded);
            }
        }

        if ($invalid) $error   = 'Ogiltiga JSON-filer: ' . implode(', ', $invalid);
        if ($merged)  {
            update_option('blogtree_audit_data', wp_json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = 'Analys klar. Sammanfogade: ' . implode(', ', $names);
        }
    }

    // ── Skapa / uppdatera integritetssida ──────────────────────────────────────
    if (isset($_POST['_nonce_generate']) && wp_verify_nonce($_POST['_nonce_generate'], 'blogtree_integrity_generate')) {
        $result = blogtree_generate_integrity_page();
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = 'Integritetssidan skapades/uppdaterades. <a href="' . esc_url(get_permalink($result)) . '" target="_blank">Visa sidan</a>';
        }
    }

    $json_raw = get_option('blogtree_audit_data', '');
    $data     = json_decode($json_raw, true);
    $status_map = ['pass' => '✅ Godkänd', 'warn' => '⚠️ Varning', 'fail' => '❌ Underkänd', 'info' => 'ℹ️ Info'];
    $sev_labels = ['critical' => '🔴 Kritisk', 'high' => '🟠 Hög', 'medium' => '🟡 Medium', 'low' => '🔵 Låg', 'info' => 'ℹ️ Info'];
    ?>
    <div class="wrap">
        <h1>Integritetsgranskning</h1>

        <?php if ($message): ?>
        <div class="notice notice-success"><p><?php echo wp_kses_post($message); ?></p></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:20px">

            <!-- ── Ladda upp & analysera ─────────────────────────────────────── -->
            <div class="postbox" style="padding:20px">
                <h2 class="hndle">Ladda upp JSON-filer</h2>
                <p class="description" style="margin-bottom:8px">
                    Stöder <strong>checklist-format</strong> (audit.json) och
                    <strong>findings-format</strong> (security-audit.json). Flera filer sammanfogas.
                </p>
                <form method="post" enctype="multipart/form-data" id="audit-upload-form">
                    <?php wp_nonce_field('blogtree_integrity_upload', '_nonce_upload'); ?>
                    <div id="audit-file-queue" style="margin-bottom:4px"></div>
                    <div class="audit-file-slot" style="margin-bottom:8px">
                        <input type="file" name="audit_files[]" accept=".json" class="audit-file-input">
                    </div>
                    <button type="submit" id="audit-start-btn" class="button button-primary" disabled>
                        Starta analysen
                    </button>
                </form>
                <script>
                (function () {
                    var form = document.getElementById('audit-upload-form');
                    var btn  = document.getElementById('audit-start-btn');
                    function addSlot() {
                        var slot = document.createElement('div');
                        slot.className = 'audit-file-slot';
                        slot.style.marginBottom = '8px';
                        var inp = document.createElement('input');
                        inp.type = 'file'; inp.name = 'audit_files[]';
                        inp.accept = '.json'; inp.className = 'audit-file-input';
                        slot.appendChild(inp);
                        form.querySelector('#audit-start-btn').before(slot);
                        bindInput(inp);
                    }
                    function bindInput(inp) {
                        inp.addEventListener('change', function () {
                            if (!this.files.length) return;
                            var lbl = this.parentElement.querySelector('.audit-uploaded-label');
                            if (!lbl) {
                                lbl = document.createElement('p');
                                lbl.className = 'audit-uploaded-label';
                                lbl.style.cssText = 'margin:0 0 4px;font-size:.85rem;color:#27ae60;font-weight:600';
                                this.parentElement.insertBefore(lbl, this);
                            }
                            lbl.textContent = 'Uppladdad: ' + this.files[0].name;
                            btn.disabled = false;
                            var empty = Array.from(form.querySelectorAll('.audit-file-input')).filter(function(i){ return !i.files.length; });
                            if (!empty.length) addSlot();
                        });
                    }
                    form.querySelectorAll('.audit-file-input').forEach(bindInput);
                })();
                </script>
            </div>

            <!-- ── Ladda ner ─────────────────────────────────────────────────── -->
            <div class="postbox" style="padding:20px">
                <h2 class="hndle">Ladda ner JSON</h2>
                <p class="description">Exportera det sammanfogade granskningsdokumentet.</p>
                <a href="<?php echo esc_url(admin_url('admin-post.php?action=blogtree_download_audit&_wpnonce=' . wp_create_nonce('blogtree_download_audit'))); ?>"
                   class="button button-secondary">Ladda ner audit.json</a>
            </div>
        </div>

        <!-- ── Skapa sida ──────────────────────────────────────────────────────── -->
        <div class="postbox" style="padding:20px;margin-top:20px">
            <h2 class="hndle">Skapa integritetssida</h2>
            <p>Skapar/uppdaterar WordPress-sidan <code>/integritet/</code> med hela rapporten.</p>
            <form method="post">
                <?php wp_nonce_field('blogtree_integrity_generate', '_nonce_generate'); ?>
                <?php submit_button('Skapa / uppdatera integritetssidan', 'primary'); ?>
            </form>
        </div>

        <!-- ── Nuvarande granskning ────────────────────────────────────────────── -->
        <?php if ($data): ?>
        <div class="postbox" style="padding:20px;margin-top:20px">
            <h2 class="hndle">Aktuell granskning</h2>

            <table class="widefat" style="margin-bottom:20px;max-width:500px">
                <tr><th>Webbplats / Plugin</th><td><?php echo esc_html($data['meta']['site'] ?? ''); ?></td></tr>
                <tr><th>Granskad</th><td><?php echo esc_html($data['meta']['audited'] ?? ''); ?></td></tr>
                <tr><th>Granskare</th><td><?php echo esc_html($data['meta']['auditor'] ?? ''); ?></td></tr>
                <tr><th>Poäng</th><td><strong><?php echo (int)($data['summary']['score'] ?? 0); ?>/100</strong></td></tr>
                <tr><th>✅ Godkänd</th><td><?php echo (int)($data['summary']['passed'] ?? 0); ?></td></tr>
                <tr><th>⚠️ Varningar</th><td><?php echo (int)($data['summary']['warnings'] ?? 0); ?></td></tr>
                <tr><th>❌ Underkänd</th><td><?php echo (int)($data['summary']['failed'] ?? 0); ?></td></tr>
                <tr><th>ℹ️ Info</th><td><?php echo (int)($data['summary']['info'] ?? 0); ?></td></tr>
            </table>

            <?php foreach (($data['categories'] ?? []) as $cat): ?>
            <h3><?php echo esc_html(($cat['icon'] ?? '') . ' ' . $cat['name']); ?></h3>
            <table class="widefat" style="margin-bottom:20px">
                <thead>
                    <tr>
                        <th style="width:22%">Kontroll / ID</th>
                        <th style="width:10%">Status</th>
                        <th style="width:10%">Allvarlighet</th>
                        <th style="width:18%">Fil:rad</th>
                        <th>Beskrivning &amp; Åtgärd</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($cat['checks'] ?? []) as $check): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($check['name']); ?></strong><br>
                        <code style="font-size:.75rem"><?php echo esc_html($check['id']); ?></code>
                    </td>
                    <td><?php echo esc_html($status_map[$check['status']] ?? $check['status']); ?></td>
                    <td><?php echo !empty($check['severity']) ? esc_html($sev_labels[$check['severity']] ?? $check['severity']) : '–'; ?></td>
                    <td>
                        <?php if (!empty($check['details'])): ?>
                        <code style="font-size:.75rem;word-break:break-all"><?php echo esc_html($check['details']); ?></code>
                        <?php else: ?>–<?php endif; ?>
                    </td>
                    <td>
                        <p style="margin:0 0 4px"><?php echo esc_html($check['description'] ?? ''); ?></p>
                        <?php if (!empty($check['code_snippet'])): ?>
                        <pre style="background:#f6f8fa;padding:6px 8px;font-size:.75rem;overflow:auto;margin:4px 0;border-radius:4px"><?php echo esc_html($check['code_snippet']); ?></pre>
                        <?php endif; ?>
                        <?php if (!empty($check['alternative'])): ?>
                        <p style="margin:4px 0 0;color:#2271b1"><strong>Åtgärd:</strong> <?php echo esc_html($check['alternative']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($check['references'])): ?>
                        <p style="margin:4px 0 0;font-size:.75rem;color:#666"><?php echo esc_html(implode(' · ', $check['references'])); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>

            <?php if (!empty($data['_recommendations'])): ?>
            <h3>Prioriterade åtgärder</h3>
            <?php
            $prio_labels = [
                'immediate_critical' => ['🔴 Åtgärda omedelbart', '#ffeaea'],
                'high_priority'      => ['🟠 Hög prioritet',      '#fff4e5'],
                'medium_priority'    => ['🟡 Medium prioritet',   '#fffde7'],
                'low_priority'       => ['🔵 Låg prioritet',      '#e8f4fd'],
                'info'               => ['ℹ️ Info',               '#f0f0f0'],
            ];
            foreach ($data['_recommendations'] as $key => $items):
                [$label, $bg] = $prio_labels[$key] ?? [$key, '#f9f9f9'];
                if (empty($items)) continue; ?>
            <div style="background:<?php echo esc_attr($bg); ?>;border-radius:6px;padding:12px 16px;margin-bottom:12px">
                <strong><?php echo esc_html($label); ?></strong>
                <ul style="margin:8px 0 0 16px">
                    <?php foreach ((array)$items as $item): ?>
                    <li style="margin-bottom:4px"><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <h3 style="margin-top:24px">Rå JSON</h3>
            <textarea class="large-text code" rows="10" readonly><?php echo esc_textarea($json_raw); ?></textarea>
        </div>
        <?php endif; ?>

    </div>
    <?php
}

// ── Ladda ner JSON ─────────────────────────────────────────────────────────────
add_action('admin_post_blogtree_download_audit', function () {
    if (!current_user_can('manage_options')) wp_die('Ej behörig');
    check_admin_referer('blogtree_download_audit');
    $json = get_option('blogtree_audit_data', '{}');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit.json"');
    echo $json;
    exit;
});

// ── Generera integritetssida ───────────────────────────────────────────────────
function blogtree_generate_integrity_page(): int|WP_Error {
    $json = get_option('blogtree_audit_data', '');
    $data = json_decode($json, true);
    if (!$data) return new WP_Error('invalid_json', 'Ingen giltig JSON-data sparad.');

    $content   = blogtree_render_integrity_content($data);
    $existing  = get_page_by_path('integritet');
    $post_data = [
        'post_title'    => 'Integritet & Öppen källkod',
        'post_name'     => 'integritet',
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'page_template' => 'page-integritet.php',
    ];

    if ($existing) {
        $post_data['ID'] = $existing->ID;
        return wp_update_post($post_data, true);
    }
    return wp_insert_post($post_data, true);
}

// ── Renderer – bygger sidans HTML ─────────────────────────────────────────────
function blogtree_render_integrity_content(array $data): string {
    $meta    = $data['meta']    ?? [];
    $summary = $data['summary'] ?? [];
    $cats    = $data['categories'] ?? [];

    $status_map = [
        'pass' => ['label' => 'Godkänd',   'class' => 'audit-pass'],
        'warn' => ['label' => 'Varning',    'class' => 'audit-warn'],
        'fail' => ['label' => 'Underkänd', 'class' => 'audit-fail'],
        'info' => ['label' => 'Info',       'class' => 'audit-info'],
    ];
    $sev_labels = [
        'critical' => 'Kritisk', 'high' => 'Hög', 'medium' => 'Medium',
        'low' => 'Låg', 'info' => 'Info',
    ];

    ob_start();
    ?>
<!-- blogtree:audit-generated -->
<div class="audit-report">

    <div class="audit-meta">
        <p>Granskning av <strong><?php echo esc_html($meta['site'] ?? ''); ?></strong>
           &middot; <?php echo esc_html($meta['audited'] ?? ''); ?>
           &middot; <?php echo esc_html($meta['auditor'] ?? ''); ?></p>
        <?php if (!empty($meta['description'])): ?>
        <p><?php echo esc_html($meta['description']); ?></p>
        <?php endif; ?>
    </div>

    <div class="audit-score">
        <div class="audit-score__number"><?php echo (int)($summary['score'] ?? 0); ?></div>
        <div class="audit-score__label">av 100</div>
        <div class="audit-score__breakdown">
            <span>✅ <?php echo (int)($summary['passed']   ?? 0); ?> godkända</span>
            <span>⚠️ <?php echo (int)($summary['warnings'] ?? 0); ?> varningar</span>
            <span>❌ <?php echo (int)($summary['failed']   ?? 0); ?> underkända</span>
        </div>
    </div>

    <?php foreach ($cats as $cat): ?>
    <section class="audit-category">
        <h2><?php echo esc_html(($cat['icon'] ?? '') . ' ' . $cat['name']); ?></h2>
        <div class="audit-checks">
        <?php foreach (($cat['checks'] ?? []) as $check):
            $s = $status_map[$check['status']] ?? ['label' => $check['status'], 'class' => 'audit-info'];
        ?>
        <div class="audit-check <?php echo esc_attr($s['class']); ?>">
            <div class="audit-check__header">
                <span class="audit-check__name"><?php echo esc_html($check['name']); ?></span>
                <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
                    <?php if (!empty($check['severity'])): ?>
                    <span class="audit-check__severity audit-sev-<?php echo esc_attr($check['severity']); ?>">
                        <?php echo esc_html($sev_labels[$check['severity']] ?? $check['severity']); ?>
                    </span>
                    <?php endif; ?>
                    <span class="audit-check__status"><?php echo esc_html($s['label']); ?></span>
                </div>
            </div>
            <?php if (!empty($check['details'])): ?>
            <p class="audit-check__file"><code><?php echo esc_html($check['details']); ?></code></p>
            <?php endif; ?>
            <p class="audit-check__desc"><?php echo esc_html($check['description']); ?></p>
            <?php if (!empty($check['code_snippet'])): ?>
            <pre class="audit-check__code"><?php echo esc_html($check['code_snippet']); ?></pre>
            <?php endif; ?>
            <?php if (!empty($check['alternative'])): ?>
            <div class="audit-check__alternative">
                <strong>Åtgärd:</strong> <?php echo esc_html($check['alternative']); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($check['references'])): ?>
            <p class="audit-check__refs"><?php echo esc_html(implode(' · ', $check['references'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if (!empty($data['_recommendations'])): ?>
    <section class="audit-category">
        <h2>📋 Prioriterade åtgärder</h2>
        <?php
        $prio = [
            'immediate_critical' => ['Åtgärda omedelbart', 'audit-fail'],
            'high_priority'      => ['Hög prioritet',      'audit-warn'],
            'medium_priority'    => ['Medium prioritet',   'audit-warn'],
            'low_priority'       => ['Låg prioritet',      'audit-info'],
            'info'               => ['Info',               'audit-info'],
        ];
        foreach ($data['_recommendations'] as $key => $items):
            [$label, $cls] = $prio[$key] ?? [$key, 'audit-info'];
            if (empty($items)) continue; ?>
        <div class="audit-check <?php echo esc_attr($cls); ?>" style="margin-bottom:var(--space-sm)">
            <strong><?php echo esc_html($label); ?></strong>
            <ul style="margin:8px 0 0 1.2em">
                <?php foreach ((array)$items as $item): ?>
                <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

</div>
    <?php
    return ob_get_clean();
}

// ── Shortcode [integritet_rapport] ─────────────────────────────────────────────
add_shortcode('integritet_rapport', function () {
    $json = get_option('blogtree_audit_data', '');
    $data = json_decode($json, true);
    if (!$data) return '<p>Ingen granskningsdata tillgänglig.</p>';
    return blogtree_render_integrity_content($data);
});
