/**
 * comments.js – Skicka kommentar och svar via AJAX
 */
(function () {
    'use strict';

    if (!window.blogtreeComments) return;

    var cfg     = window.blogtreeComments;
    var ajaxurl = cfg.ajaxurl;
    var nonce   = cfg.nonce;

    // ── Skicka formulär ───────────────────────────────────────────────────────────
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('.comment-form');
        if (!form) return;
        e.preventDefault();

        var postId   = form.dataset.postId;
        var parentId = form.dataset.parentId || '0';
        var textarea = form.querySelector('textarea[name="content"]');
        var content  = textarea ? textarea.value.trim() : '';
        var btn      = form.querySelector('button[type="submit"]');

        if (!content) return;

        if (btn) btn.disabled = true;

        var body = new URLSearchParams({
            action:    'blogtree_post_comment',
            post_id:   postId,
            parent_id: parentId,
            content:   content,
            nonce:     nonce,
        });

        fetch(ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    alert(data.data || 'Något gick fel.');
                    if (btn) btn.disabled = false;
                    return;
                }

                var commentId = data.data.comment_id;
                var listId    = 'comment-list-' + postId;

                if (parentId !== '0') {
                    // Svar: lägg till i rätt nästlad lista
                    var parentItem  = document.getElementById('comment-' + parentId);
                    var replyList   = parentItem ? parentItem.querySelector('.comment-list--replies') : null;
                    if (!replyList) {
                        replyList = document.createElement('ol');
                        replyList.className = 'comment-list comment-list--replies';
                        if (parentItem) parentItem.appendChild(replyList);
                    }

                    replyList.insertAdjacentHTML('beforeend', buildCommentHTML(data.data, cfg.displayName, commentId));

                    // Stäng svar-formulär
                    var replyFormWrap = document.getElementById('reply-form-' + parentId);
                    if (replyFormWrap) replyFormWrap.hidden = true;
                } else {
                    // Toppnivå: lägg till i huvudlistan (skapa om den saknas)
                    var list = document.getElementById(listId);
                    if (!list) {
                        list = document.createElement('ol');
                        list.className = 'comment-list';
                        list.id = listId;
                        var section = document.getElementById('comments');
                        var formWrap = document.getElementById('comment-form-wrap');
                        if (formWrap) section.insertBefore(list, formWrap);
                    }
                    list.insertAdjacentHTML('beforeend', buildCommentHTML(data.data, cfg.displayName, commentId));

                    // Uppdatera räknaren
                    var heading = document.querySelector('.comments-section__title');
                    if (heading) {
                        var count = document.querySelectorAll('.comment-item').length;
                        heading.textContent = count + ' kommentarer';
                    }
                }

                textarea.value = '';
                if (btn) btn.disabled = false;
            })
            .catch(function () {
                alert('Något gick fel. Försök igen.');
                if (btn) btn.disabled = false;
            });
    });

    // ── Svar-knapp: visa/dölj formulär ───────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.comment-reply-btn');
        if (!btn) return;

        var commentId = btn.dataset.commentId;
        var wrap      = document.getElementById('reply-form-' + commentId);
        if (!wrap) return;

        var isOpen = !wrap.hidden;
        wrap.hidden = isOpen;
        if (!isOpen) {
            var input = wrap.querySelector('textarea');
            if (input) input.focus();
        }
    });

    // ── Avbryt svar ───────────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.reply-cancel-btn');
        if (!btn) return;
        var wrap = document.getElementById('reply-form-' + btn.dataset.commentId);
        if (wrap) wrap.hidden = true;
    });

    // ── Bygg HTML för ny kommentar ────────────────────────────────────────────────
    function buildCommentHTML(data, displayName, commentId) {
        var now = new Date();
        var dateStr = now.toLocaleDateString('sv-SE', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        var avatar = cfg.avatarUrl
            ? '<img src="' + cfg.avatarUrl + '" width="36" height="36" class="avatar" alt="">'
            : '<span class="avatar avatar-placeholder"></span>';

        return '<li class="comment-item" id="comment-' + commentId + '">' +
            '<div class="comment-body">' +
            '<div class="comment-meta">' + avatar +
            '<div><strong class="comment-author">' + escHtml(displayName) + '</strong>' +
            '<time class="comment-date">' + dateStr + '</time></div></div>' +
            '<div class="comment-content"><p>' + escHtml(data.content || '') + '</p></div>' +
            '<div class="comment-actions">' +
            '<button class="report-comment-btn" data-comment-id="' + commentId + '" aria-label="Rapportera kommentar">' +
            '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>' +
            ' Rapportera</button></div>' +
            '</div></li>';
    }

    // ── Gästkommentarsmodal ───────────────────────────────────────────────────────
    var guestOverlay = document.getElementById('guest-comment-overlay');
    var guestStatus  = document.getElementById('guest-comment-status');
    var guestSubmit  = document.getElementById('guest-comment-submit');
    var guestCancel  = document.getElementById('guest-comment-cancel');
    var guestPostId  = null;

    if (guestOverlay) {
        // Öppna
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.comments-section__guest-btn');
            if (!btn) return;
            guestPostId = btn.dataset.postId;
            document.getElementById('guest-name').value    = '';
            document.getElementById('guest-email').value   = '';
            document.getElementById('guest-content').value = '';
            guestStatus.hidden = true;
            guestSubmit.disabled = false;
            guestOverlay.hidden = false;
        });

        // Stäng
        guestCancel.addEventListener('click', function () { guestOverlay.hidden = true; });
        guestOverlay.addEventListener('click', function (e) {
            if (e.target === guestOverlay) guestOverlay.hidden = true;
        });

        // Skicka
        guestSubmit.addEventListener('click', function () {
            var name    = document.getElementById('guest-name').value.trim();
            var email   = document.getElementById('guest-email').value.trim();
            var content = document.getElementById('guest-content').value.trim();

            if (!name || !email || !content) {
                showGuestStatus('Fyll i alla fält.', 'error');
                return;
            }

            guestSubmit.disabled = true;

            var body = new URLSearchParams({
                action:      'blogtree_post_guest_comment',
                post_id:     guestPostId,
                guest_name:  name,
                guest_email: email,
                content:     content,
                nonce:       nonce,
            });

            fetch(ajaxurl, { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        showGuestStatus(data.data.message, 'success');
                        setTimeout(function () { guestOverlay.hidden = true; }, 4000);
                    } else {
                        showGuestStatus(data.data || 'Något gick fel.', 'error');
                        guestSubmit.disabled = false;
                    }
                })
                .catch(function () {
                    showGuestStatus('Något gick fel. Försök igen.', 'error');
                    guestSubmit.disabled = false;
                });
        });

        function showGuestStatus(msg, type) {
            guestStatus.textContent = msg;
            guestStatus.className = 'report-modal__status report-modal__status--' + type;
            guestStatus.hidden = false;
        }
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}());
