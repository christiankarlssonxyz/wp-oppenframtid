<?php
/**
 * page-sok.php – Söksida
 *
 * Söksida med fuzzy-sökning mot REST API, filter och realtidsresultat.
 * Används automatiskt för sidan med slug "sok".
 */
get_header();
?>

<main class="site-main">
    <div class="container search-page">

        <h1 class="search-page__heading">Sök</h1>

        <input type="search" id="search-input" class="search-page__input"
               placeholder="Skriv för att söka…" autocomplete="off" spellcheck="false" aria-label="Sökterm">

        <div class="search-filter-box">
            <h2 class="search-filter-box__title">Inlägg</h2>
            <div class="search-filter-box__row">
                <div class="search-filter-col">
                    <label class="search-filter__label" for="search-sort">Sortering</label>
                    <select id="search-sort" class="search-filter__select">
                        <option value="newest">Nyast</option>
                        <option value="oldest">Äldst</option>
                    </select>
                </div>
                <div class="search-filter-col">
                    <label class="search-filter__label" for="search-date">Datum</label>
                    <select id="search-date" class="search-filter__select">
                        <option value="">När som helst</option>
                        <option value="today">Idag</option>
                        <option value="week">Senaste veckan</option>
                        <option value="month">Senaste månaden</option>
                        <option value="halfyear">6 månader</option>
                    </select>
                </div>
                <div class="search-filter-col search-filter-col--check">
                    <label class="search-filter__checkbox">
                        <input type="checkbox" id="search-title-only">
                        <span>Sök endast rubriker</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="search-results" id="search-results" aria-live="polite" aria-atomic="true"></div>

    </div>
</main>

<?php get_footer(); ?>
