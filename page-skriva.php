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
                    <input type="hidden" id="mikro-content-input" name="content">

                    <!-- Redigeringsarea -->
                    <div class="mikro-editor">

                        <!-- Toolbar -->
                        <div class="mikro-toolbar" role="toolbar" aria-label="Formateringsverktyg">
                            <button type="button" class="mikro-toolbar__btn" data-cmd="bold" title="Fetstil (Ctrl+B)">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                            </button>
                            <button type="button" class="mikro-toolbar__btn" data-cmd="italic" title="Kursiv (Ctrl+I)">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
                            </button>
                            <div class="mikro-toolbar__sep"></div>
                            <button type="button" class="mikro-toolbar__btn" data-cmd="insertUnorderedList" title="Punktlista">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
                            </button>
                            <button type="button" class="mikro-toolbar__btn" data-cmd="insertOrderedList" title="Numrerad lista">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
                            </button>
                            <div class="mikro-toolbar__sep"></div>
                            <button type="button" class="mikro-toolbar__btn" id="mikro-emoji-btn" title="Emoji">
                                😊
                            </button>
                            <div class="mikro-toolbar__sep"></div>
                            <label class="mikro-toolbar__btn mikro-toolbar__img-label" title="Lägg till bild">
                                <input type="file" id="mikro-img-input" accept="image/*" hidden>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </label>
                        </div>

                        <!-- Emoji-picker -->
                        <div id="mikro-emoji-picker" class="mikro-emoji-picker" hidden>
                            <div class="mikro-emoji-picker__grid">
                                <?php
                                $emojis = [
                                    '😀','😂','😍','🤔','😎','🥳','😢','😡','🙏','👍',
                                    '👎','❤️','🔥','✅','⚡','🎉','💡','📢','🚀','🌍',
                                    '📰','🗳️','⚖️','🏛️','📊','💬','🤝','✊','👏','🌱',
                                    '⚠️','❌','💰','🏆','📝','🔍','📌','🕐','💪','🧵',
                                ];
                                foreach ($emojis as $e): ?>
                                <button type="button" class="mikro-emoji-btn" data-emoji="<?php echo $e; ?>"><?php echo $e; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Contenteditable -->
                        <div id="mikro-content"
                             class="mikro-write-form__editor"
                             contenteditable="true"
                             data-placeholder="Vad tänker du på?"
                             role="textbox"
                             aria-multiline="true"
                             aria-label="Inläggstext"></div>

                        <!-- Bilduppladdnings-feedback -->
                        <div id="mikro-img-status" hidden></div>

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
