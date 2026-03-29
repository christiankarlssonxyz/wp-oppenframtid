/**
 * admin-comments.js – Admin-interaktioner på kommentarstrådar
 *
 * - Expandera/kollapsa lång kommentartext
 * - Markera som läst (AJAX)
 * - Gilla (AJAX)
 * - Svar-formulär (toggle + skicka via blogtreeComments AJAX)
 */
(function () {
    'use strict';

    if (!window.blogtreeAdminComments) return;

    var cfg    = window.blogtreeAdminComments;
    var notice = document.getElementById('admin-notice');

    function showNotice(msg, type) {
        if (!notice) return;
        notice.textContent = msg;
        notice.className = 'members-notice members-notice--' + type;
        notice.hidden = false;
        setTimeout(function () { notice.hidden = true; }, 3500);
    }

    // ── Expandera/kollapsa kommentartext ──────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.comment-expand-btn');
        if (!btn) return;

        var wrap   = btn.closest('.mod-comment__text');
        var rest   = wrap ? wrap.querySelector('.comment-rest') : null;
        if (!rest) return;

        var open = !rest.hidden;
        rest.hidden = open;
        btn.textContent = open ? '… visa mer' : ' visa mindre';
    });

    // ── Markera som läst / Gilla ──────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.admin-action-btn[data-action]');
        if (!btn) return;

        var action    = btn.dataset.action;
        var commentId = btn.dataset.commentId;
        var nonce     = btn.dataset.nonce;

        var ajaxAction = action === 'mark_read'
            ? 'blogtree_admin_mark_read'
            : 'blogtree_admin_like';

        btn.disabled = true;

        var body = new URLSearchParams({
            action:     ajaxAction,
            comment_id: commentId,
            nonce:      nonce,
        });

        fetch(cfg.ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    showNotice(data.data || 'Något gick fel.', 'error');
                    btn.disabled = false;
                    return;
                }

                if (action === 'mark_read') {
                    var isRead = data.data.read;
                    btn.textContent = isRead ? 'Markerad som läst' : 'Markera som läst';
                    btn.classList.toggle('admin-action-btn--active', isRead);
                    var card = btn.closest('.mod-comment--thread');
                    if (card) card.classList.toggle('mod-comment--read', isRead);
                    // Ta bort kortet från listan om det markeras som läst
                    if (isRead && card) {
                        setTimeout(function () {
                            card.style.transition = 'opacity .3s';
                            card.style.opacity    = '0';
                            setTimeout(function () { card.remove(); }, 320);
                        }, 600);
                    }
                } else {
                    var liked = data.data.liked;
                    btn.textContent = liked ? '❤ Gillar' : '♡ Gilla';
                    btn.classList.toggle('admin-action-btn--active', liked);

                    // Uppdatera badge i headern
                    var header = btn.closest('.mod-comment--thread').querySelector('.mod-comment__header');
                    var badge  = header ? header.querySelector('.mod-admin-liked-badge') : null;
                    if (liked && !badge && header) {
                        var b = document.createElement('span');
                        b.className   = 'mod-admin-liked-badge';
                        b.textContent = '❤ Admin gillar';
                        header.appendChild(b);
                    } else if (!liked && badge) {
                        badge.remove();
                    }
                }

                btn.disabled = false;
            })
            .catch(function () {
                showNotice('Något gick fel.', 'error');
                btn.disabled = false;
            });
    });

    // ── Svar-formulär toggle ──────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.admin-reply-toggle');
        if (!btn) return;
        var id   = btn.dataset.commentId;
        var form = document.getElementById('admin-reply-' + id);
        if (!form) return;
        form.hidden = !form.hidden;
        if (!form.hidden) {
            var ta = form.querySelector('textarea');
            if (ta) ta.focus();
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.admin-reply-cancel');
        if (!btn) return;
        var form = document.getElementById('admin-reply-' + btn.dataset.commentId);
        if (form) form.hidden = true;
    });

    // Stäng svar-form och markera som läst när svar skickas
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('.comment-form--admin');
        if (!form) return;

        // Efter submit (hanteras av comments.js) — markera förälderkommentaren som läst
        var parentId = form.dataset.parentId;
        if (!parentId) return;

        var readBtn = document.querySelector(
            '.admin-read-btn[data-comment-id="' + parentId + '"]'
        );
        if (readBtn && !readBtn.classList.contains('admin-action-btn--active')) {
            // Klicka automatiskt på "markera som läst" efter svar publiceras
            setTimeout(function () { readBtn.click(); }, 800);
        }

        // Stäng svar-formuläret
        var replyForm = document.getElementById('admin-reply-' + parentId);
        if (replyForm) setTimeout(function () { replyForm.hidden = true; }, 500);
    });

}());
