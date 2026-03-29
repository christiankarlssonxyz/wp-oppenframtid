<?php
/**
 * inc/mikroinlagg.php – Mikroinlägg (social media-inspirerade korta meddelanden)
 *
 * - Registrerar custom post type `mikroinlagg`
 * - Kopplar befintliga taxonomier: topic, category, post_tag
 * - Metaboxar: synlighet, Mastodon-länk, Threads-länk
 * - AJAX: spara mikroinlägg från frontend
 */

// ── Registrera CPT ────────────────────────────────────────────────────────────

add_action('init', function () {
    register_post_type('mikroinlagg', [
        'labels' => [
            'name'               => 'Mikroinlägg',
            'singular_name'      => 'Mikroinlägg',
            'add_new'            => 'Lägg till nytt',
            'add_new_item'       => 'Lägg till mikroinlägg',
            'edit_item'          => 'Redigera mikroinlägg',
            'new_item'           => 'Nytt mikroinlägg',
            'view_item'          => 'Visa mikroinlägg',
            'search_items'       => 'Sök mikroinlägg',
            'not_found'          => 'Inga mikroinlägg hittades',
            'not_found_in_trash' => 'Inga mikroinlägg i papperskorgen',
        ],
        'public'            => true,
        'show_in_rest'      => true,
        'supports'          => ['editor', 'comments', 'custom-fields'],
        'has_archive'       => true,
        'rewrite'           => ['slug' => 'mikroinlagg'],
        'menu_icon'         => 'dashicons-format-status',
        'menu_position'     => 5,
        'show_in_nav_menus' => true,
    ]);
});

// ── Koppla taxonomier ─────────────────────────────────────────────────────────

add_action('init', function () {
    register_taxonomy_for_object_type('topic',    'mikroinlagg');
    register_taxonomy_for_object_type('category', 'mikroinlagg');
    register_taxonomy_for_object_type('post_tag', 'mikroinlagg');
});

// ── Metaboxar ─────────────────────────────────────────────────────────────────

add_action('add_meta_boxes', function () {
    add_meta_box(
        'mikroinlagg_meta',
        'Inställningar',
        'blogtree_mikroinlagg_meta_box',
        'mikroinlagg',
        'side',
        'high'
    );
});

function blogtree_mikroinlagg_meta_box($post) {
    wp_nonce_field('blogtree_mikroinlagg_meta', 'blogtree_mikroinlagg_nonce');
    $visibility    = get_post_meta($post->ID, '_mikro_visibility',    true) ?: 'public';
    $mastodon_url  = get_post_meta($post->ID, '_mikro_mastodon_url',  true);
    $threads_url   = get_post_meta($post->ID, '_mikro_threads_url',   true);
    ?>
    <p>
        <label for="mikro_visibility"><strong>Synlighet</strong></label><br>
        <select id="mikro_visibility" name="mikro_visibility" style="width:100%">
            <option value="public"  <?php selected($visibility, 'public');  ?>>Alla besökare</option>
            <option value="members" <?php selected($visibility, 'members'); ?>>Bara inloggade</option>
        </select>
    </p>
    <p>
        <label for="mikro_mastodon_url"><strong>Mastodon-länk</strong></label><br>
        <input type="url" id="mikro_mastodon_url" name="mikro_mastodon_url"
               value="<?php echo esc_attr($mastodon_url); ?>" style="width:100%" placeholder="https://mastodon.social/...">
    </p>
    <p>
        <label for="mikro_threads_url"><strong>Threads-länk</strong></label><br>
        <input type="url" id="mikro_threads_url" name="mikro_threads_url"
               value="<?php echo esc_attr($threads_url); ?>" style="width:100%" placeholder="https://www.threads.net/...">
    </p>
    <?php
}

add_action('save_post_mikroinlagg', function ($post_id) {
    if (!isset($_POST['blogtree_mikroinlagg_nonce'])) return;
    if (!wp_verify_nonce($_POST['blogtree_mikroinlagg_nonce'], 'blogtree_mikroinlagg_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $visibility = in_array($_POST['mikro_visibility'] ?? '', ['public', 'members'])
        ? $_POST['mikro_visibility'] : 'public';
    update_post_meta($post_id, '_mikro_visibility', $visibility);

    $mastodon = esc_url_raw($_POST['mikro_mastodon_url'] ?? '');
    update_post_meta($post_id, '_mikro_mastodon_url', $mastodon);

    $threads = esc_url_raw($_POST['mikro_threads_url'] ?? '');
    update_post_meta($post_id, '_mikro_threads_url', $threads);
});

// ── Teckenbegränsning i admin ─────────────────────────────────────────────────

add_action('admin_footer-post.php',     'blogtree_mikroinlagg_char_counter');
add_action('admin_footer-post-new.php', 'blogtree_mikroinlagg_char_counter');
function blogtree_mikroinlagg_char_counter() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'mikroinlagg') return;
    ?>
    <script>
    (function () {
        var MAX = 500;
        function setup() {
            var editor = document.querySelector('#content');
            if (!editor) return;
            var counter = document.createElement('p');
            counter.id = 'mikro-char-counter';
            counter.style.cssText = 'margin:4px 0 0;font-size:.85em;color:#666';
            editor.parentNode.insertBefore(counter, editor.nextSibling);
            function update() {
                var len = editor.value.replace(/<[^>]+>/g, '').length;
                counter.textContent = len + ' / ' + MAX + ' tecken';
                counter.style.color = len > MAX ? '#c0392b' : '#666';
            }
            editor.addEventListener('input', update);
            update();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
    })();
    </script>
    <?php
}

// ── AJAX: spara mikroinlägg från frontend ─────────────────────────────────────

add_action('wp_ajax_blogtree_save_mikro', 'blogtree_ajax_save_mikro');
function blogtree_ajax_save_mikro() {
    check_ajax_referer('blogtree_mikro_save', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Behörighet saknas.'], 403);
    }

    $content = wp_strip_all_tags(wp_unslash($_POST['content'] ?? ''));
    $content = trim($content);

    if (empty($content)) {
        wp_send_json_error(['message' => 'Innehållet får inte vara tomt.']);
    }
    if (mb_strlen($content) > 500) {
        wp_send_json_error(['message' => 'Max 500 tecken.']);
    }

    $visibility   = in_array($_POST['visibility'] ?? '', ['public', 'members']) ? $_POST['visibility'] : 'public';
    $mastodon_url = esc_url_raw($_POST['mastodon_url'] ?? '');
    $threads_url  = esc_url_raw($_POST['threads_url']  ?? '');
    $action_type  = in_array($_POST['action_type'] ?? '', ['publish', 'draft', 'schedule']) ? $_POST['action_type'] : 'publish';

    $status    = 'publish';
    $post_date = null;

    if ($action_type === 'draft') {
        $status = 'draft';
    } elseif ($action_type === 'schedule') {
        $schedule = sanitize_text_field($_POST['schedule_date'] ?? '');
        if (!$schedule) {
            wp_send_json_error(['message' => 'Ange ett datum för schemaläggning.']);
        }
        $timestamp = strtotime($schedule);
        if (!$timestamp || $timestamp <= time()) {
            wp_send_json_error(['message' => 'Datumet måste ligga i framtiden.']);
        }
        $status    = 'future';
        $post_date = date('Y-m-d H:i:s', $timestamp);
    }

    $post_data = [
        'post_type'    => 'mikroinlagg',
        'post_content' => $content,
        'post_status'  => $status,
    ];
    if ($post_date) {
        $post_data['post_date'] = $post_date;
    }

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    update_post_meta($post_id, '_mikro_visibility',   $visibility);
    update_post_meta($post_id, '_mikro_mastodon_url', $mastodon_url);
    update_post_meta($post_id, '_mikro_threads_url',  $threads_url);

    // Taxonomier
    $topic_ids = array_map('intval', (array) ($_POST['topics'] ?? []));
    $topic_ids = array_filter($topic_ids);
    if ($topic_ids) {
        wp_set_post_terms($post_id, $topic_ids, 'topic');
    }

    $cat_ids = array_map('intval', (array) ($_POST['categories'] ?? []));
    $cat_ids = array_filter($cat_ids);
    if ($cat_ids) {
        wp_set_post_terms($post_id, $cat_ids, 'category');
    }

    $tags_raw = sanitize_text_field($_POST['tags'] ?? '');
    if ($tags_raw) {
        $tags = array_map('trim', explode(',', $tags_raw));
        $tags = array_filter($tags);
        wp_set_post_terms($post_id, $tags, 'post_tag');
    }

    $permalink = get_permalink($post_id);

    wp_send_json_success([
        'message'   => $status === 'draft' ? 'Utkast sparat.' : ($status === 'future' ? 'Inlägg schemalagt.' : 'Publicerat!'),
        'permalink' => $permalink,
        'status'    => $status,
    ]);
}

// ── Hjälpfunktion: rendera ett mikroinlägg-kort ───────────────────────────────

function blogtree_mikro_card($post_id) {
    $post         = get_post($post_id);
    $visibility   = get_post_meta($post_id, '_mikro_visibility',   true) ?: 'public';
    $mastodon_url = get_post_meta($post_id, '_mikro_mastodon_url', true);
    $threads_url  = get_post_meta($post_id, '_mikro_threads_url',  true);

    // Dölj members-inlägg för icke-inloggade
    if ($visibility === 'members' && !is_user_logged_in()) return;

    $author_id   = $post->post_author;
    $avatar      = get_avatar($author_id, 40);
    $author_name = get_the_author_meta('display_name', $author_id);
    $time_diff   = blogtree_mikro_time_diff($post->post_date_gmt);
    $permalink   = get_permalink($post_id);

    $topics   = get_the_terms($post_id, 'topic')    ?: [];
    $cats     = get_the_terms($post_id, 'category') ?: [];
    $tags     = get_the_terms($post_id, 'post_tag') ?: [];
    $comments = (int) get_comments_number($post_id);
    ?>
    <article class="mikro-card" id="mikro-<?php echo $post_id; ?>">
        <div class="mikro-card__avatar">
            <?php echo $avatar; ?>
        </div>
        <div class="mikro-card__body">
            <div class="mikro-card__meta">
                <span class="mikro-card__author"><?php echo esc_html($author_name); ?></span>
                <a href="<?php echo esc_url($permalink); ?>" class="mikro-card__time">
                    <time datetime="<?php echo esc_attr($post->post_date); ?>"><?php echo esc_html($time_diff); ?></time>
                </a>
                <?php if ($visibility === 'members'): ?>
                <span class="mikro-card__visibility" title="Bara för inloggade">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <?php endif; ?>
            </div>

            <div class="mikro-card__content">
                <?php echo apply_filters('the_content', $post->post_content); ?>
            </div>

            <?php if ($topics || $cats || $tags): ?>
            <div class="mikro-card__chips">
                <?php foreach ($topics as $t): ?>
                    <a href="<?php echo esc_url(get_term_link($t)); ?>" class="mikro-chip mikro-chip--topic"><?php echo esc_html($t->name); ?></a>
                <?php endforeach; ?>
                <?php foreach ($cats as $c):
                    if ($c->slug === 'uncategorized') continue; ?>
                    <a href="<?php echo esc_url(get_term_link($c)); ?>" class="mikro-chip mikro-chip--cat"><?php echo esc_html($c->name); ?></a>
                <?php endforeach; ?>
                <?php foreach ($tags as $tag): ?>
                    <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="mikro-chip mikro-chip--tag">#<?php echo esc_html(ltrim($tag->name, '#')); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mikro-card__footer">
                <a href="<?php echo esc_url($permalink); ?>#comments" class="mikro-card__comments">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <?php echo $comments > 0 ? $comments : 'Kommentera'; ?>
                </a>

                <?php if ($mastodon_url || $threads_url): ?>
                <div class="mikro-card__crosspost">
                    <?php if ($mastodon_url): ?>
                    <a href="<?php echo esc_url($mastodon_url); ?>" class="mikro-crosspost-link" target="_blank" rel="noopener noreferrer" title="Visa på Mastodon">
                        <svg viewBox="0 0 74 79" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M73.7 17.5c-1-7-7.4-12.5-14.7-13.7C57.4 3.5 50.5 3 43.7 3h-.1c-6.8 0-13.7.5-15.3.8C21.3 5 14.7 10.2 13.6 17.3c-.5 3.5-.5 7-.5 10.5 0 4.2 0 8.5.5 12.7 1 7.2 7.6 13.3 15 14.5 4.3.7 8.7 1 13 1 4.7 0 9.3-.4 13.9-1.3l-.2-3s-6.3 2-13.7 2c-7.2 0-14.7-.4-16.7-3.5-.5-.7-.8-1.5-1-2.4 5.3.7 10.7 1 16.1 1 5.2 0 10.5-.3 15.6-1 7.3-1 13.7-6.7 14.5-14 .3-2.7.4-5.4.4-8.2 0-3.3.1-6.5-.3-9.7zM62 38h-8.7V20.5c0-4-1.6-6-5-6-3.7 0-5.5 2.4-5.5 7.1v10.2h-8.6V21.6c0-4.7-1.8-7.1-5.5-7.1-3.3 0-5 2-5 6V38h-8.7V19.7c0-4 1-7.1 3-9.4 2.1-2.3 4.8-3.4 8.2-3.4 3.9 0 6.9 1.5 8.8 4.6l1.9 3.2 1.9-3.2c2-3.1 5-4.6 8.8-4.6 3.4 0 6.1 1.1 8.2 3.4 2 2.3 3 5.5 3 9.4V38z"/></svg>
                        Mastodon
                    </a>
                    <?php endif; ?>
                    <?php if ($threads_url): ?>
                    <a href="<?php echo esc_url($threads_url); ?>" class="mikro-crosspost-link" target="_blank" rel="noopener noreferrer" title="Visa på Threads">
                        <svg viewBox="0 0 192 192" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M141.5 88.7c-1.2-.6-2.5-1.1-3.8-1.6-.5-18.4-10.4-29-27.1-29.1-10.3 0-19.4 4.4-24.8 12.1l10.2 7c3.4-5.1 8.7-7.8 15.9-7.8 8.6 0 13.7 5.1 15.5 11.3-2.7-.5-5.6-.7-8.5-.7-12.7 0-26.2 6.7-26.2 21.4 0 12.7 11.1 20.6 23.6 20.6 11.5 0 21.2-6.3 25.4-16.9h.3c1.7-4.4 2.5-9.3 2.5-14.7 0-.6 0-1.2-.1-1.7zm-26.6 28.7c-7.5 0-12.2-3.6-12.2-9 0-8 8.7-11 18.3-11 2.8 0 5.6.3 8.2.8-1.1 10.7-7.4 19.2-14.3 19.2zM96 16C52.3 16 16 52.3 16 96s36.3 80 80 80 80-36.3 80-80-36.3-80-80-80zm2.6 125.1c-27.4 0-46.3-17.4-46.3-44.7 0-25.8 18.8-45.4 46.3-45.4 9.6 0 18.7 2.7 26.2 7.7l-7.4 10.3c-5.6-3.7-12-5.5-18.8-5.5-19.3 0-31.8 14.2-31.8 32.9 0 19.2 12.5 31.2 31.8 31.2 9.4 0 17.6-3.1 23.7-8.6l8.2 9.8c-8.3 7.3-19 11.3-31.9 11.3z"/></svg>
                        Threads
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

// ── Hjälpfunktion: relativ tid ────────────────────────────────────────────────

function blogtree_mikro_time_diff($date_gmt) {
    $now  = time();
    $then = strtotime($date_gmt . ' UTC');
    $diff = $now - $then;

    if ($diff < 60)        return 'nyss';
    if ($diff < 3600)      return floor($diff / 60) . ' min sedan';
    if ($diff < 86400)     return floor($diff / 3600) . ' tim sedan';
    if ($diff < 604800)    return floor($diff / 86400) . ' dag' . (floor($diff / 86400) > 1 ? 'ar' : '') . ' sedan';
    return date_i18n('j M Y', strtotime($date_gmt));
}
