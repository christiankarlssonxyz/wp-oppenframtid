<?php
/**
 * Template Name: Konto – Följer
 *
 * page-konto-foljer.php – Följda ämnen
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/konto/foljer/')));
    exit;
}

get_header();

$followed_ids = array_filter(array_map('intval',
    (array) get_user_meta(get_current_user_id(), 'blogtree_followed_topics', true)
));
$topics = $followed_ids ? get_terms([
    'taxonomy'   => 'topic',
    'include'    => $followed_ids,
    'hide_empty' => false,
]) : [];
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/konto/')); ?>" class="konto-back">← Mitt konto</a>
        <h1 class="konto-page__title">Följer</h1>
    </div>

    <?php if ($topics && !is_wp_error($topics)): ?>
    <ul class="konto-topics-list">
        <?php foreach ($topics as $topic):
            $color = get_term_meta($topic->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
        ?>
        <li class="konto-topic-item" data-term-id="<?php echo esc_attr($topic->term_id); ?>">
            <a href="<?php echo esc_url(get_term_link($topic)); ?>"
               class="konto-topic-item__link"
               style="--topic-color: <?php echo esc_attr($color); ?>">
                <span class="konto-topic-item__dot"></span>
                <span class="konto-topic-item__name"><?php echo esc_html($topic->name); ?></span>
                <span class="konto-topic-item__count"><?php echo (int) $topic->count; ?> inlägg</span>
            </a>
            <button class="konto-unfollow-btn follow-btn is-following"
                    data-term-id="<?php echo esc_attr($topic->term_id); ?>">
                Följer
            </button>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="konto-empty">Du följer inga ämnen ännu. Gå till ett ämne och klicka "Följ".</p>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<script>
(function () {
    // Dölj raden när man avföljer
    document.addEventListener('blogtreeUnfollowed', function (e) {
        var termId = e.detail && e.detail.termId;
        if (!termId) return;
        var item = document.querySelector('.konto-topic-item[data-term-id="' + termId + '"]');
        if (item) item.remove();
    });
}());
</script>

<?php get_footer(); ?>
