/**
 * follow.js – Följ ämne
 *
 * Skickar en AJAX-förfrågan när användaren klickar på följ-knappen.
 * Uppdaterar knappens text direkt.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.follow-btn');
        if (!btn) return;

        var termId = btn.dataset.termId;
        if (!termId) return;

        btn.disabled = true;

        var body = new URLSearchParams({
            action:  'blogtree_follow_topic',
            term_id: termId,
            nonce:   blogtreeFollowAjax.nonce,
        });

        fetch(blogtreeFollowAjax.url, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                btn.classList.toggle('is-following', data.data.following);
                btn.textContent = data.data.following ? 'Följer' : 'Följ';
            })
            .finally(function () { btn.disabled = false; });
    });
}());
