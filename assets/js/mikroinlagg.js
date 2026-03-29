(function () {
    'use strict';

    var MAX     = 500;
    var editor  = document.getElementById('mikro-content');
    var counter = document.getElementById('mikro-char-count');
    var form    = document.getElementById('mikro-write-form');

    if (!editor) return;

    // ── Placeholder ──────────────────────────────────────────────────────────
    editor.addEventListener('focus', function () {
        if (editor.textContent.trim() === '') editor.innerHTML = '';
    });
    editor.addEventListener('blur', function () {
        if (editor.textContent.trim() === '') editor.innerHTML = '';
    });

    // ── Teckentäknare ────────────────────────────────────────────────────────
    function updateCounter() {
        var len = editor.textContent.length;
        if (counter) {
            counter.textContent = len;
            counter.parentElement.classList.toggle('is-over', len > MAX);
        }
    }
    editor.addEventListener('input', updateCounter);

    // ── Toolbar ──────────────────────────────────────────────────────────────
    document.querySelectorAll('.mikro-toolbar__btn[data-cmd]').forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
            e.preventDefault();
            editor.focus();
            document.execCommand(btn.dataset.cmd, false, null);
            updateCounter();
        });
    });

    // ── Emoji-picker ─────────────────────────────────────────────────────────
    var emojiBtn    = document.getElementById('mikro-emoji-btn');
    var emojiPicker = document.getElementById('mikro-emoji-picker');

    if (emojiBtn && emojiPicker) {
        emojiBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            emojiPicker.hidden = !emojiPicker.hidden;
        });

        emojiPicker.querySelectorAll('.mikro-emoji-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editor.focus();
                document.execCommand('insertText', false, btn.dataset.emoji);
                emojiPicker.hidden = true;
                updateCounter();
            });
        });

        document.addEventListener('click', function () {
            emojiPicker.hidden = true;
        });
        emojiPicker.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    // ── Bilduppladdning ──────────────────────────────────────────────────────
    var imgInput  = document.getElementById('mikro-img-input');
    var imgStatus = document.getElementById('mikro-img-status');

    if (imgInput && window.blogtreeMikro) {
        imgInput.addEventListener('change', function () {
            var file = imgInput.files[0];
            if (!file) return;

            showImgStatus('Laddar upp…', '');

            var data = new FormData();
            data.append('action', 'blogtree_mikro_upload_image');
            data.append('nonce',  blogtreeMikro.nonce);
            data.append('image',  file);

            fetch(blogtreeMikro.ajaxurl, { method: 'POST', body: data })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        editor.focus();
                        var img = '<img src="' + res.data.url + '" alt="" style="max-width:100%;border-radius:8px;margin-top:8px;">';
                        document.execCommand('insertHTML', false, img);
                        showImgStatus('Bild tillagd.', 'ok');
                        setTimeout(function () { imgStatus.hidden = true; }, 2000);
                    } else {
                        showImgStatus(res.data.message || 'Uppladdning misslyckades.', 'err');
                    }
                })
                .catch(function () { showImgStatus('Nätverksfel.', 'err'); });

            imgInput.value = '';
        });
    }

    function showImgStatus(msg, type) {
        if (!imgStatus) return;
        imgStatus.textContent = msg;
        imgStatus.className   = 'mikro-img-status mikro-img-status--' + type;
        imgStatus.hidden      = false;
    }

    // ── Tidsinställnings-toggle ──────────────────────────────────────────────
    var scheduleToggle = document.getElementById('mikro-btn-schedule-toggle');
    var scheduleRow    = document.getElementById('mikro-schedule-row');
    var btnSchedule    = document.getElementById('mikro-btn-schedule');
    var btnPublish     = document.getElementById('mikro-btn-publish');
    var scheduleActive = false;

    if (scheduleToggle && scheduleRow && btnSchedule) {
        scheduleToggle.addEventListener('click', function () {
            scheduleActive        = !scheduleActive;
            scheduleRow.hidden    = !scheduleActive;
            btnSchedule.hidden    = !scheduleActive;
            btnPublish.hidden     = scheduleActive;
            scheduleToggle.classList.toggle('is-active', scheduleActive);
            scheduleToggle.textContent = scheduleActive ? 'Avbryt' : 'Tidsinställ';
        });
    }

    // ── Formulärskickning via AJAX ───────────────────────────────────────────
    var feedback = document.getElementById('mikro-write-feedback');
    var contentInput = document.getElementById('mikro-content-input');

    if (!form || !window.blogtreeMikro) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var submitBtn  = e.submitter || form.querySelector('[type="submit"]');
        var actionType = submitBtn ? submitBtn.value : 'publish';
        var textLen    = editor.textContent.trim().length;

        if (!textLen) {
            showFeedback('Skriv något innan du publicerar.', 'error');
            return;
        }
        if (textLen > MAX) {
            showFeedback('Max 500 tecken tillåtna.', 'error');
            return;
        }
        if (actionType === 'schedule') {
            var dateInput = document.getElementById('mikro-schedule-date');
            if (!dateInput || !dateInput.value) {
                showFeedback('Välj ett datum och tid.', 'error');
                return;
            }
        }

        contentInput.value = editor.innerHTML;

        var data = new FormData(form);
        data.set('action',      'blogtree_save_mikro');
        data.set('nonce',       document.querySelector('[name="blogtree_mikro_nonce"]').value);
        data.set('action_type', actionType);

        setLoading(submitBtn, true);
        hideFeedback();

        fetch(blogtreeMikro.ajaxurl, { method: 'POST', body: data })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showFeedback(res.data.message, 'success');
                    editor.innerHTML = '';
                    form.reset();
                    updateCounter();
                    if (res.data.status === 'publish') {
                        setTimeout(function () { window.location.href = res.data.permalink; }, 1200);
                    }
                } else {
                    showFeedback(res.data.message || 'Något gick fel.', 'error');
                }
            })
            .catch(function () { showFeedback('Nätverksfel – försök igen.', 'error'); })
            .finally(function () { setLoading(submitBtn, false); });
    });

    function showFeedback(msg, type) {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.className   = 'mikro-write-feedback mikro-write-feedback--' + type;
        feedback.hidden      = false;
    }
    function hideFeedback() { if (feedback) feedback.hidden = true; }
    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        if (loading) btn.dataset.label = btn.textContent;
        else if (btn.dataset.label) btn.textContent = btn.dataset.label;
    }
})();
