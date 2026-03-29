<?php
/**
 * inc/read-more.php – "Läs mer"-sektion på enskilda inlägg
 *
 * - Admin-inställningssida under Inställningar → Läs mer
 * - Urvalslogik: taggar → kategorier → ämnen → slumpmässiga
 */

// ── Admin-inställningssida ────────────────────────────────────────────────────

add_action('admin_menu', function () {
    add_options_page(
        'Läs mer-inställningar',
        'Läs mer',
        'manage_options',
        'blogtree-read-more',
        'blogtree_read_more_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('blogtree_read_more', 'blogtree_read_more_count',  ['default' => 4]);
    register_setting('blogtree_read_more', 'blogtree_read_more_image',  ['default' => '1']);
    register_setting('blogtree_read_more', 'blogtree_read_more_layout', ['default' => 'grid']);
});

function blogtree_read_more_settings_page(): void {
    if (!current_user_can('manage_options')) return;
    $count  = (int)    get_option('blogtree_read_more_count',  4);
    $image  = (string) get_option('blogtree_read_more_image',  '1');
    $layout = (string) get_option('blogtree_read_more_layout', 'grid');
    ?>
    <div class="wrap">
        <h1>Läs mer – inställningar</h1>
        <form method="post" action="options.php">
            <?php settings_fields('blogtree_read_more'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rm_count">Antal inlägg</label></th>
                    <td>
                        <input type="number" id="rm_count" name="blogtree_read_more_count"
                               value="<?php echo esc_attr($count); ?>" min="1" max="8" class="small-text">
                        <p class="description">Hur många relaterade inlägg som visas (1–8).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Visa bild</th>
                    <td>
                        <label>
                            <input type="checkbox" name="blogtree_read_more_image" value="1" <?php checked($image, '1'); ?>>
                            Visa inläggets bild i läs mer-sektionen
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Visningssätt</th>
                    <td>
                        <label>
                            <input type="radio" name="blogtree_read_more_layout" value="grid" <?php checked($layout, 'grid'); ?>>
                            Grid
                        </label>
                        &nbsp;&nbsp;
                        <label>
                            <input type="radio" name="blogtree_read_more_layout" value="list" <?php checked($layout, 'list'); ?>>
                            Lista
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ── Hämta relaterade inlägg ───────────────────────────────────────────────────

function blogtree_get_related_posts(int $post_id, int $count): array {
    $found   = [];
    $exclude = [$post_id];

    // 1. Gemensamma taggar
    $tags = wp_get_post_terms($post_id, 'post_tag', ['fields' => 'ids']);
    if (!is_wp_error($tags) && $tags) {
        $q = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'post__not_in'   => $exclude,
            'tax_query'      => [['taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tags]],
            'orderby'        => 'rand',
            'fields'         => 'ids',
        ]);
        foreach ($q->posts as $id) {
            $found[]   = (int) $id;
            $exclude[] = (int) $id;
            if (count($found) >= $count) return $found;
        }
    }

    // 2. Gemensamma kategorier
    $cats = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);
    if (!is_wp_error($cats) && $cats) {
        $q = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count - count($found),
            'post__not_in'   => $exclude,
            'tax_query'      => [['taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats]],
            'orderby'        => 'rand',
            'fields'         => 'ids',
        ]);
        foreach ($q->posts as $id) {
            $found[]   = (int) $id;
            $exclude[] = (int) $id;
            if (count($found) >= $count) return $found;
        }
    }

    // 3. Gemensamma ämnen (topic)
    $topics = wp_get_post_terms($post_id, 'topic', ['fields' => 'ids']);
    if (!is_wp_error($topics) && $topics) {
        $q = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count - count($found),
            'post__not_in'   => $exclude,
            'tax_query'      => [['taxonomy' => 'topic', 'field' => 'term_id', 'terms' => $topics]],
            'orderby'        => 'rand',
            'fields'         => 'ids',
        ]);
        foreach ($q->posts as $id) {
            $found[]   = (int) $id;
            $exclude[] = (int) $id;
            if (count($found) >= $count) return $found;
        }
    }

    // 4. Slumpmässiga
    if (count($found) < $count) {
        $q = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count - count($found),
            'post__not_in'   => $exclude,
            'orderby'        => 'rand',
            'fields'         => 'ids',
        ]);
        foreach ($q->posts as $id) {
            $found[] = (int) $id;
        }
    }

    return $found;
}

// ── Rendera läs mer-sektionen ─────────────────────────────────────────────────

function blogtree_render_read_more(int $post_id): void {
    $count  = (int)    get_option('blogtree_read_more_count',  4);
    $image  = (string) get_option('blogtree_read_more_image',  '1');
    $layout = (string) get_option('blogtree_read_more_layout', 'grid');

    $ids = blogtree_get_related_posts($post_id, $count);
    if (!$ids) return;

    $layout_class = $layout === 'list' ? 'read-more__list--list' : 'read-more__list--grid';
    ?>
    <section class="read-more">
        <h2 class="read-more__title">Läs mer</h2>
        <div class="read-more__list <?php echo esc_attr($layout_class); ?>">
            <?php foreach ($ids as $id):
                $post      = get_post($id);
                $permalink = get_permalink($id);
                $title     = get_the_title($id);
                $topics    = get_the_terms($id, 'topic');
                $topic     = (!is_wp_error($topics) && $topics) ? $topics[0] : null;
            ?>
            <a href="<?php echo esc_url($permalink); ?>" class="read-more-card">
                <?php if ($image === '1' && has_post_thumbnail($id)): ?>
                <div class="read-more-card__img">
                    <?php echo get_the_post_thumbnail($id, 'thumbnail'); ?>
                </div>
                <?php endif; ?>
                <div class="read-more-card__body">
                    <?php if ($topic): ?>
                    <span class="read-more-card__topic"><?php echo esc_html($topic->name); ?></span>
                    <?php endif; ?>
                    <h3 class="read-more-card__title"><?php echo esc_html($title); ?></h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
