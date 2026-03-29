/**
 * saved.js – Spara inlägg + Kopiera länk
 */
(function () {
    'use strict';

    // ── Spara-knapp ─────────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.save-btn');
        if (!btn) return;

        var postId = btn.dataset.postId;
        if (!postId || !window.blogtreeSaved) return;

        btn.disabled = true;

        var body = new URLSearchParams({
            action:  'blogtree_save_post',
            post_id: postId,
            nonce:   blogtreeSaved.nonce,
        });

        fetch(blogtreeSaved.ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                var saved = data.data.saved;
                btn.classList.toggle('is-saved', saved);
                var label = btn.querySelector('.save-btn__label');
                if (label) label.textContent = saved ? 'Sparad' : 'Spara';
                btn.setAttribute('aria-label', saved ? 'Ta bort från sparade' : 'Spara inlägg');
            })
            .finally(function () { btn.disabled = false; });
    });

    // ── Kopiera länk ────────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.copy-btn');
        if (!btn) return;

        var url = btn.dataset.url || window.location.href;

        navigator.clipboard.writeText(url).then(function () {
            var label = btn.querySelector('.copy-btn__label');
            btn.classList.add('is-copied');
            if (label) label.textContent = 'Kopierad!';

            setTimeout(function () {
                btn.classList.remove('is-copied');
                if (label) label.textContent = 'Kopiera länk';
            }, 2000);
        });
    });
}());
