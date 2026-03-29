/**
 * reports.js – Rapportera kommentar + Överklagan
 */
(function () {
    'use strict';

    if (!window.blogtreeReports) return;

    var cfg = window.blogtreeReports;

    // ── Bygg och injicera modal ──────────────────────────────────────────────────
    function buildModal() {
        var reasonOptions = Object.entries(cfg.reasons).map(function (entry) {
            return '<label class="report-modal__reason"><input type="radio" name="report_reason" value="' +
                entry[0] + '"> ' + entry[1] + '</label>';
        }).join('');

        var emailField = cfg.loggedIn ? '' :
            '<div class="report-modal__field">' +
            '<label>Din e-postadress (för verifiering)</label>' +
            '<input type="email" id="report-email" placeholder="din@epost.se">' +
            '</div>';

        var html =
            '<div class="report-modal-overlay" id="report-modal-overlay" hidden>' +
            '  <div class="report-modal" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">' +
            '    <h2 id="report-modal-title" class="report-modal__title">Rapportera kommentar</h2>' +
            '    <p class="report-modal__sub">Välj anledning:</p>' +
            '    <div class="report-modal__reasons">' + reasonOptions + '</div>' +
            emailField +
            '    <p class="report-modal__status" id="report-status" hidden></p>' +
            '    <div class="report-modal__footer">' +
            '      <button class="btn btn--primary" id="report-submit-btn">Skicka rapport</button>' +
            '      <button class="btn btn--ghost" id="report-cancel-btn">Avbryt</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        var appealHtml =
            '<div class="report-modal-overlay" id="appeal-modal-overlay" hidden>' +
            '  <div class="report-modal" role="dialog" aria-modal="true" aria-labelledby="appeal-modal-title">' +
            '    <h2 id="appeal-modal-title" class="report-modal__title">Överklaga beslut</h2>' +
            '    <p class="report-modal__sub">Beskriv varför du anser att kommentaren bör tas bort:</p>' +
            '    <textarea id="appeal-message" class="report-modal__textarea" rows="5" placeholder="Ditt meddelande…"></textarea>' +
            '    <div class="report-modal__field">' +
            '      <label>Din e-postadress <span class="report-modal__hint">(valfritt)</span></label>' +
            '      <input type="email" id="appeal-email" placeholder="din@epost.se">' +
            '    </div>' +
            '    <p class="report-modal__status" id="appeal-status" hidden></p>' +
            '    <div class="report-modal__footer">' +
            '      <button class="btn btn--primary" id="appeal-submit-btn">Skicka överklagan</button>' +
            '      <button class="btn btn--ghost" id="appeal-cancel-btn">Avbryt</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.insertAdjacentHTML('beforeend', html + appealHtml);
    }

    buildModal();

    var overlay      = document.getElementById('report-modal-overlay');
    var submitBtn    = document.getElementById('report-submit-btn');
    var cancelBtn    = document.getElementById('report-cancel-btn');
    var statusEl     = document.getElementById('report-status');
    var appealOverlay = document.getElementById('appeal-modal-overlay');
    var activeCommentId = null;

    // ── Öppna rapport-modal ──────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.report-comment-btn');
        if (!btn) return;
        activeCommentId = btn.dataset.commentId;
        // Rensa
        document.querySelectorAll('input[name="report_reason"]').forEach(function (r) { r.checked = false; });
        var emailField = document.getElementById('report-email');
        if (emailField) emailField.value = '';
        statusEl.hidden = true;
        submitBtn.disabled = false;
        overlay.hidden = false;
    });

    // ── Stäng modal ──────────────────────────────────────────────────────────────
    cancelBtn.addEventListener('click', function () { overlay.hidden = true; });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.hidden = true;
    });

    // ── Skicka rapport ───────────────────────────────────────────────────────────
    submitBtn.addEventListener('click', function () {
        var reason = document.querySelector('input[name="report_reason"]:checked');
        if (!reason) {
            showStatus(statusEl, 'Välj en anledning.', 'error');
            return;
        }

        var body = new URLSearchParams({
            action:     'blogtree_report_comment',
            comment_id: activeCommentId,
            reason:     reason.value,
            nonce:      cfg.nonce,
        });

        if (!cfg.loggedIn) {
            var email = (document.getElementById('report-email') || {}).value || '';
            if (!email) {
                showStatus(statusEl, 'Ange din e-postadress.', 'error');
                return;
            }
            body.set('email', email);
        }

        submitBtn.disabled = true;
        fetch(cfg.ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showStatus(statusEl, data.data.message, 'success');
                    setTimeout(function () { overlay.hidden = true; }, 2500);
                } else {
                    showStatus(statusEl, data.data || 'Något gick fel.', 'error');
                    submitBtn.disabled = false;
                }
            });
    });

    // ── Överklagan ───────────────────────────────────────────────────────────────
    var appealCommentId = null;
    var appealStatus    = document.getElementById('appeal-status');
    var appealSubmit    = document.getElementById('appeal-submit-btn');
    var appealCancel    = document.getElementById('appeal-cancel-btn');

    // Öppna från URL-parameter
    var params = new URLSearchParams(window.location.search);
    if (params.has('blogtree_appeal')) {
        appealCommentId = params.get('blogtree_appeal');
        appealOverlay.hidden = false;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.appeal-btn');
        if (!btn) return;
        appealCommentId = btn.dataset.commentId;
        appealOverlay.hidden = false;
    });

    appealCancel.addEventListener('click', function () { appealOverlay.hidden = true; });
    appealOverlay.addEventListener('click', function (e) {
        if (e.target === appealOverlay) appealOverlay.hidden = true;
    });

    appealSubmit.addEventListener('click', function () {
        var message = document.getElementById('appeal-message').value.trim();
        var email   = (document.getElementById('appeal-email') || {}).value || '';

        if (!message) {
            showStatus(appealStatus, 'Skriv ett meddelande.', 'error');
            return;
        }

        var body = new URLSearchParams({
            action:     'blogtree_appeal',
            comment_id: appealCommentId,
            message:    message,
            email:      email,
            nonce:      cfg.nonce,
        });

        appealSubmit.disabled = true;
        fetch(cfg.ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showStatus(appealStatus, data.data.message, 'success');
                    setTimeout(function () { appealOverlay.hidden = true; }, 2500);
                } else {
                    showStatus(appealStatus, data.data || 'Något gick fel.', 'error');
                    appealSubmit.disabled = false;
                }
            });
    });

    function showStatus(el, msg, type) {
        el.textContent = msg;
        el.className = 'report-modal__status report-modal__status--' + type;
        el.hidden = false;
    }

}());
