<?php
/**
 * Template Name: Alla kommentarer
 *
 * page-medlemmar-kommentarer-alla.php
 * Slug: medlemmar/kommentarer/alla
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$page_num = max(1, (int) ($_GET['paged'] ?? 1));
$per_page = 30;
$offset   = ($page_num - 1) * $per_page;
$search   = sanitize_text_field($_GET['s'] ?? '');

$args = [
    'status'  => 'approve',
    'number'  => $per_page,
    'offset'  => $offset,
    'orderby' => 'comment_date',
    'order'   => 'DESC',
];
if ($search) {
    $args['search'] = $search;
}

$comments    = get_comments($args);
$total_count = get_comments(array_merge($args, ['count' => true, 'number' => 0, 'offset' => 0]));
$pages       = ceil($total_count / $per_page);
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/medlemmar/')); ?>" class="konto-back">← Medlemshantering</a>
        <h1 class="konto-page__title">Kommentarer</h1>
    </div>

    <?php blogtree_mod_tab_nav('alla'); ?>

    <div class="mod-all-toolbar">
        <form method="get" class="mod-all-search">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                   placeholder="Sök kommentarer…" class="mod-all-search__input">
            <button type="submit" class="btn btn--ghost">Sök</button>
            <?php if ($search): ?>
            <a href="<?php echo esc_url(remove_query_arg('s')); ?>" class="btn btn--ghost">Rensa</a>
            <?php endif; ?>
        </form>
        <span class="mod-all-count"><?php echo (int) $total_count; ?> kommentarer</span>
    </div>

    <?php if ($comments): ?>
    <div class="mod-comments-list">
        <?php foreach ($comments as $comment):
            $post_title = get_the_title($comment->comment_post_ID);
            $post_url   = get_permalink($comment->comment_post_ID);
            $is_liked   = get_comment_meta($comment->comment_ID, 'blogtree_admin_liked', true);
            $content    = $comment->comment_content;
            $short      = mb_strlen($content) > 100;
            $preview    = $short ? mb_substr($content, 0, 100) : $content;
            $rest       = $short ? mb_substr($content, 100) : '';
        ?>
        <div class="mod-comment" id="mod-comment-<?php echo esc_attr($comment->comment_ID); ?>">
            <div class="mod-comment__header">
                <strong class="mod-comment__author"><?php echo esc_html($comment->comment_author); ?></strong>
                <a href="<?php echo esc_url($post_url); ?>" class="mod-comment__post" target="_blank">
                    <?php echo esc_html($post_title); ?>
                </a>
                <time class="mod-comment__date">
                    <?php echo esc_html(date_i18n('j M Y H:i', strtotime($comment->comment_date))); ?>
                </time>
                <?php if ($is_liked): ?>
                <span class="mod-admin-liked-badge">❤ Admin gillar</span>
                <?php endif; ?>
            </div>
            <div class="mod-comment__text">
                <span class="comment-preview"><?php echo esc_html($preview); ?></span><?php if ($short): ?><span class="comment-rest" hidden><?php echo esc_html($rest); ?></span><button class="comment-expand-btn" type="button">… visa mer</button><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php echo paginate_links([
            'total'   => $pages,
            'current' => $page_num,
        ]); ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="konto-empty">Inga kommentarer hittades.</p>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
