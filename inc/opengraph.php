<?php
/**
 * inc/opengraph.php – Open Graph & Twitter Card meta-taggar
 *
 * Matar ut rätt delningsbild beroende på inläggets ämne.
 *
 * Fallback-ordning:
 *   1. Ämnets delningsbild (wpblogtree_topic_og_image_id)
 *   2. Inläggets featured image
 *   3. Sajt-logotypen (custom_logo)
 */

add_action('wp_head', function () {
    if (!is_singular()) return;

    $post_id = get_queried_object_id();
    $post    = get_post($post_id);

    // ── Titel ─────────────────────────────────────────────────────────────────
    $title = get_the_title($post_id);

    // ── Beskrivning ───────────────────────────────────────────────────────────
    $excerpt = has_excerpt($post_id)
        ? strip_tags(get_the_excerpt($post_id))
        : wp_trim_words(strip_tags($post->post_content), 30, '…');

    // ── URL ───────────────────────────────────────────────────────────────────
    $url = get_permalink($post_id);

    // ── Bild: fallback-kedja ──────────────────────────────────────────────────
    $image_url = '';

    // 1. Ämnets delningsbild
    $topics = get_the_terms($post_id, 'topic');
    if ($topics && !is_wp_error($topics)) {
        foreach ($topics as $topic) {
            $og_id = (int) get_term_meta($topic->term_id, 'wpblogtree_topic_og_image_id', true);
            if ($og_id) {
                $src = wp_get_attachment_image_src($og_id, 'full');
                if ($src) {
                    $image_url = $src[0];
                    break;
                }
            }
        }
    }

    // 2. Inläggets featured image
    if (!$image_url && has_post_thumbnail($post_id)) {
        $src = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'blogtree-hero');
        if ($src) {
            $image_url = $src[0];
        }
    }

    // 3. Sajt-logotyp
    if (!$image_url) {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $src = wp_get_attachment_image_src($logo_id, 'full');
            if ($src) {
                $image_url = $src[0];
            }
        }
    }

    $type = is_page() ? 'website' : 'article';

    ?>
<!-- Open Graph -->
<meta property="og:type"        content="<?php echo esc_attr($type); ?>">
<meta property="og:title"       content="<?php echo esc_attr($title); ?>">
<meta property="og:description" content="<?php echo esc_attr($excerpt); ?>">
<meta property="og:url"         content="<?php echo esc_url($url); ?>">
<meta property="og:site_name"   content="<?php echo esc_attr(get_bloginfo('name')); ?>">
<?php if ($image_url): ?>
<meta property="og:image"        content="<?php echo esc_url($image_url); ?>">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?php echo esc_attr($title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($excerpt); ?>">
<?php if ($image_url): ?>
<meta name="twitter:image"       content="<?php echo esc_url($image_url); ?>">
<?php endif; ?>
    <?php
}, 1);
