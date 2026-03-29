<?php
/**
 * page-skriva.php – Frontend-formulär för att skriva mikroinlägg
 * Slug: skriva
 * Endast för admins.
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/logga-in/?redirect_to=' . urlencode(get_permalink())));
    exit;
}

get_header();

$topics = get_terms(['taxonomy' => 'topic',    'hide_empty' => false]);
$cats   = get_terms(['taxonomy' => 'category', 'hide_empty' => false, 'exclude' => [get_option('default_category')]]);
?>

<div class="container">
    <div class="mikro-layout">
        <main class="mikro-main">

            <div class="mikro-write-card">
                <h1 class="mikro-write-card__title">Skriv mikroinlägg</h1>

                <div id="mikro-write-feedback" class="mikro-write-feedback" hidden></div>

                <form id="mikro-write-form" class="mikro-write-form">
                    <?php wp_nonce_field('blogtree_mikro_save', 'blogtree_mikro_nonce'); ?>

                    <!-- Textarea -->
                    <div class="mikro-write-form__field">
                        <textarea id="mikro-content" name="content" class="mikro-write-form__textarea"
                                  placeholder="Vad tänker du på?" maxlength="500" rows="4" required></textarea>
                        <div class="mikro-write-form__counter">
                            <span id="mikro-char-count">0</span> / 500
                        </div>
                    </div>

                    <!-- Synlighet -->
                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label">Synlighet</label>
                        <div class="mikro-write-form__options">
                            <label class="mikro-radio">
                                <input type="radio" name="visibility" value="public" checked>
                                <span>Alla</span>
                            </label>
                            <label class="mikro-radio">
                                <input type="radio" name="visibility" value="members">
                                <span>Bara inloggade</span>
                            </label>
                        </div>
                    </div>

                    <!-- Ämne -->
                    <?php if ($topics): ?>
                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label" for="mikro-topic">Ämne</label>
                        <select id="mikro-topic" name="topics[]" class="mikro-write-form__select">
                            <option value="">— Välj ämne —</option>
                            <?php foreach ($topics as $t): ?>
                            <option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html($t->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Kategori -->
                    <?php if ($cats): ?>
                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label" for="mikro-cat">Kategori</label>
                        <select id="mikro-cat" name="categories[]" class="mikro-write-form__select">
                            <option value="">— Välj kategori —</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?php echo (int) $c->term_id; ?>"><?php echo esc_html($c->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Taggar -->
                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label" for="mikro-tags">Taggar</label>
                        <input type="text" id="mikro-tags" name="tags" class="mikro-write-form__input"
                               placeholder="tagg1, tagg2, tagg3">
                    </div>

                    <!-- Crosspost-länkar -->
                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label" for="mikro-mastodon">Mastodon-länk</label>
                        <input type="url" id="mikro-mastodon" name="mastodon_url" class="mikro-write-form__input"
                               placeholder="https://mastodon.social/...">
                    </div>

                    <div class="mikro-write-form__row">
                        <label class="mikro-write-form__label" for="mikro-threads">Threads-länk</label>
                        <input type="url" id="mikro-threads" name="threads_url" class="mikro-write-form__input"
                               placeholder="https://www.threads.net/...">
                    </div>

                    <!-- Schemaläggning -->
                    <div class="mikro-write-form__row" id="mikro-schedule-row" hidden>
                        <label class="mikro-write-form__label" for="mikro-schedule-date">Publiceras</label>
                        <input type="datetime-local" id="mikro-schedule-date" name="schedule_date"
                               class="mikro-write-form__input">
                    </div>

                    <!-- Knappar -->
                    <div class="mikro-write-form__actions">
                        <button type="submit" name="action_type" value="publish" class="btn btn--primary" id="mikro-btn-publish">
                            Publicera nu
                        </button>
                        <button type="submit" name="action_type" value="draft" class="btn btn--secondary" id="mikro-btn-draft">
                            Spara utkast
                        </button>
                        <button type="button" class="btn btn--ghost" id="mikro-btn-schedule-toggle">
                            Tidsinställ
                        </button>
                        <button type="submit" name="action_type" value="schedule" class="btn btn--primary" id="mikro-btn-schedule" hidden>
                            Schemalägg
                        </button>
                    </div>

                </form>
            </div>

            <!-- Senaste utkast / schemalagda (admin-vy) -->
            <?php
            $drafts = new WP_Query([
                'post_type'      => 'mikroinlagg',
                'post_status'    => ['draft', 'future'],
                'posts_per_page' => 5,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]);
            if ($drafts->have_posts()): ?>
            <div class="mikro-drafts">
                <h2 class="mikro-drafts__title">Utkast &amp; schemalagda</h2>
                <?php while ($drafts->have_posts()): $drafts->the_post(); ?>
                <div class="mikro-draft-item">
                    <span class="mikro-draft-item__status mikro-draft-item__status--<?php echo get_post_status(); ?>">
                        <?php echo get_post_status() === 'future' ? 'Schemalagd' : 'Utkast'; ?>
                    </span>
                    <span class="mikro-draft-item__content"><?php echo esc_html(wp_trim_words(get_the_content(), 15, '...')); ?></span>
                    <a href="<?php echo esc_url(get_edit_post_link()); ?>" class="mikro-draft-item__edit">Redigera</a>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php endif; ?>

        </main>
        <?php get_sidebar(); ?>
    </div>
</div>

<?php get_footer(); ?>
