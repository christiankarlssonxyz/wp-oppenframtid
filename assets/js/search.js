(function () {
    'use strict';

    var overlay    = document.getElementById('search-overlay');
    var backdrop   = document.getElementById('search-backdrop');
    var panel      = overlay ? overlay.querySelector('.search-overlay__panel') : null;
    var input      = document.getElementById('search-input');
    var results    = document.getElementById('search-results');
    var trigger    = document.getElementById('search-trigger');
    var closeBtn   = document.getElementById('search-close');
    var sortSel    = document.getElementById('search-sort');
    var dateSel    = document.getElementById('search-date');
    var titleOnly  = document.getElementById('search-title-only');

    if (!overlay || !input) return;

    var allPosts   = [];
    var postsReady = false;
    var fetchDone  = false;

    // ── Öppna / stäng ────────────────────────────────────────────────────────────
    function open() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
        input.focus();
        if (!fetchDone) fetchPosts();
        else runSearch();
    }

    function close() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    // ── Hämta inlägg från REST API ────────────────────────────────────────────────
    function fetchPosts() {
        fetchDone = true;
        results.innerHTML = '<p class="search-results__status">Laddar…</p>';
        var base = blogtreeSearch.restUrl + 'wp/v2/posts?per_page=100&_fields=id,title,date,link,excerpt';
        fetch(base)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                allPosts   = data || [];
                postsReady = true;
                runSearch();
            })
            .catch(function () {
                results.innerHTML = '<p class="search-results__status">Kunde inte hämta inlägg.</p>';
            });
    }

    // ── Fuzzy-sökning ─────────────────────────────────────────────────────────────
    function score(pattern, str) {
        if (!pattern) return 0;
        var p = pattern.toLowerCase();
        var s = str.toLowerCase();
        if (s === p)             return 1000;
        if (s.startsWith(p))    return 900;
        var ci = s.indexOf(p);
        if (ci > -1)            return 800 - ci;

        // tecken-för-tecken fuzzy
        var pi = 0, pts = 0, consec = 0;
        for (var si = 0; si < s.length && pi < p.length; si++) {
            if (p[pi] === s[si]) {
                pts += 10 + consec * 6;
                consec++;
                pi++;
            } else {
                consec = 0;
            }
        }
        return pi < p.length ? -1 : pts;
    }

    function stripTags(html) {
        return html.replace(/<[^>]+>/g, '');
    }

    function dateLimit(value) {
        var d = new Date();
        if (value === 'today')    { d.setHours(0, 0, 0, 0); return d; }
        if (value === 'week')     { d.setDate(d.getDate() - 7); return d; }
        if (value === 'month')    { d.setMonth(d.getMonth() - 1); return d; }
        if (value === 'halfyear') { d.setMonth(d.getMonth() - 6); return d; }
        return null;
    }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        var safe    = escHtml(text);
        var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return safe.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark>$1</mark>');
    }

    function escHtml(str) {
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;');
    }

    // ── Kör sökning + rendering ───────────────────────────────────────────────────
    function runSearch() {
        if (!postsReady) return;

        var query     = input.value.trim();
        var sort      = sortSel.value;
        var dateVal   = dateSel.value;
        var titleOnlyChecked = titleOnly.checked;
        var limit     = dateLimit(dateVal);

        var pool = allPosts.filter(function (post) {
            if (limit && new Date(post.date) < limit) return false;
            return true;
        });

        var scored;
        if (query) {
            scored = pool.map(function (post) {
                var title   = stripTags(post.title.rendered);
                var excerpt = titleOnlyChecked ? '' : stripTags(post.excerpt.rendered);
                var s       = Math.max(score(query, title), titleOnlyChecked ? -1 : score(query, excerpt));
                return { post: post, score: s };
            }).filter(function (r) { return r.score >= 0; });
            scored.sort(function (a, b) { return b.score - a.score; });
            pool = scored.map(function (r) { return r.post; });
        }

        // Datum-sortering (sekundär om query finns, primär annars)
        if (!query || sort) {
            pool.sort(function (a, b) {
                var da = new Date(a.date), db = new Date(b.date);
                return sort === 'oldest' ? da - db : db - da;
            });
        }

        render(pool, query);
    }

    function render(posts, query) {
        if (!posts.length) {
            results.innerHTML = '<p class="search-results__status">' +
                (query ? 'Inga resultat för "' + escHtml(query) + '".' : 'Inga inlägg inom valt tidsintervall.') +
                '</p>';
            return;
        }

        var html = '<ul class="search-results__list">';
        posts.forEach(function (post) {
            var title   = stripTags(post.title.rendered);
            var excerpt = stripTags(post.excerpt.rendered).replace(/\s+/g, ' ').trim().substring(0, 140);
            var date    = new Date(post.date).toLocaleDateString('sv-SE', { year: 'numeric', month: 'long', day: 'numeric' });

            html += '<li class="search-results__item">';
            html += '<a href="' + escHtml(post.link) + '" class="search-results__link">';
            html += '<span class="search-results__title">' + highlight(title, query) + '</span>';
            html += '<span class="search-results__date">' + date + '</span>';
            if (excerpt) {
                html += '<span class="search-results__excerpt">' + highlight(excerpt, query) + '</span>';
            }
            html += '</a></li>';
        });
        html += '</ul>';
        results.innerHTML = html;
    }

    // ── Event listeners ───────────────────────────────────────────────────────────
    if (trigger)   trigger.addEventListener('click', open);
    if (closeBtn)  closeBtn.addEventListener('click', close);
    if (backdrop)  backdrop.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); open(); }
    });

    input.addEventListener('input', runSearch);
    sortSel.addEventListener('change', runSearch);
    dateSel.addEventListener('change', runSearch);
    titleOnly.addEventListener('change', runSearch);
})();
