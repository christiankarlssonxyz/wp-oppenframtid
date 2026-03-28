/**
 * likes.js – Gilla-knapp
 *
 * Skickar en AJAX-förfrågan när användaren klickar på gilla-knappen.
 * Uppdaterar knappens utseende direkt utan att ladda om sidan.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.like-btn');
        if (!btn) return;

        // Kräver inloggning
        if (btn.dataset.requireLogin) {
            window.location.href = '/logga-in/?redirect=' + encodeURIComponent(window.location.href);
            return;
        }

        var postId = btn.dataset.postId;
        if (!postId) return;

        btn.disabled = true;

        var body = new URLSearchParams({
            action:  'blogtree_like',
            post_id: postId,
            nonce:   blogtreeAjax.nonce,
        });

        fetch(blogtreeAjax.url, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                btn.classList.toggle('is-liked', data.data.liked);
                var countEl = btn.querySelector('.like-btn__count');
                if (data.data.count > 0) {
                    if (!countEl) {
                        countEl = document.createElement('span');
                        countEl.className = 'like-btn__count';
                        btn.appendChild(countEl);
                    }
                    countEl.textContent = data.data.count;
                } else if (countEl) {
                    countEl.remove();
                }
            })
            .finally(function () { btn.disabled = false; });
    });
}());
