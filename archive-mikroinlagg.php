<?php
/**
 * archive-mikroinlagg.php – Flödessida för mikroinlägg
 * URL: /mikroinlagg/
 */
get_header();

$paged      = get_query_var('paged') ?: 1;
$filter_tax = sanitize_key($_GET['filter'] ?? '');
$filter_val = sanitize_text_field($_GET['term'] ?? '');

// Hämta alla topics, kategorier för filterrad
$topics = get_terms(['taxonomy' => 'topic',    'hide_empty' => true]);
$cats   = get_terms(['taxonomy' => 'category', 'hide_empty' => true, 'exclude' => [get_option('default_category')]]);

$query_args = [
    'post_type'      => 'mikroinlagg',
    'paged'          => $paged,
    'posts_per_page' => 20,
    'post_status'    => 'publish',
];

if ($filter_tax && $filter_val) {
    $query_args['tax_query'] = [[
        'taxonomy' => $filter_tax,
        'field'    => 'slug',
        'terms'    => $filter_val,
    ]];
}

$loop = new WP_Query($query_args);
?>

<div class="container">
    <div class="mikro-layout">

        <!-- ── Huvud ──────────────────────────────────────────────────────── -->
        <main class="mikro-main">

            <div class="mikro-header">
                <h1 class="mikro-header__title">Mikroinlägg</h1>
                <?php if (current_user_can('manage_options')): ?>
                <a href="<?php echo esc_url(home_url('/skriva/')); ?>" class="btn btn--primary mikro-header__write-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nytt inlägg
                </a>
                <?php endif; ?>
            </div>

            <!-- Filterrad -->
            <?php if ($topics || $cats): ?>
            <div class="mikro-filters">
                <a href="<?php echo esc_url(home_url('/mikroinlagg/')); ?>"
                   class="mikro-filter-btn <?php echo (!$filter_tax) ? 'is-active' : ''; ?>">Alla</a>
                <?php foreach ($topics as $t): ?>
                <a href="<?php echo esc_url(add_query_arg(['filter' => 'topic', 'term' => $t->slug], home_url('/mikroinlagg/'))); ?>"
                   class="mikro-filter-btn <?php echo ($filter_tax === 'topic' && $filter_val === $t->slug) ? 'is-active' : ''; ?>">
                    <?php echo esc_html($t->name); ?>
                </a>
                <?php endforeach; ?>
                <?php foreach ($cats as $c): ?>
                <a href="<?php echo esc_url(add_query_arg(['filter' => 'category', 'term' => $c->slug], home_url('/mikroinlagg/'))); ?>"
                   class="mikro-filter-btn <?php echo ($filter_tax === 'category' && $filter_val === $c->slug) ? 'is-active' : ''; ?>">
                    <?php echo esc_html($c->name); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Flöde -->
            <div class="mikro-feed">
                <?php if ($loop->have_posts()):
                    while ($loop->have_posts()): $loop->the_post();
                        blogtree_mikro_card(get_the_ID());
                    endwhile;
                    wp_reset_postdata();
                else: ?>
                <p class="mikro-empty">Inga mikroinlägg ännu.</p>
                <?php endif; ?>
            </div>

            <!-- Paginering -->
            <?php if ($loop->max_num_pages > 1): ?>
            <div class="pagination">
                <?php echo paginate_links([
                    'total'     => $loop->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                ]); ?>
            </div>
            <?php endif; ?>

        </main>

        <!-- ── Sidebar ────────────────────────────────────────────────────── -->
        <?php get_sidebar(); ?>

    </div>
</div>

<?php get_footer(); ?>
