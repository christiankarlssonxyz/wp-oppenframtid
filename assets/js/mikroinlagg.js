(function () {
    'use strict';

    // ── Teckentäknare ────────────────────────────────────────────────────────
    var textarea = document.getElementById('mikro-content');
    var counter  = document.getElementById('mikro-char-count');
    var MAX      = 500;

    if (textarea && counter) {
        function updateCounter() {
            var len = textarea.value.length;
            counter.textContent = len;
            counter.parentElement.classList.toggle('is-over', len > MAX);
        }
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    // ── Tidsinställnings-toggle ──────────────────────────────────────────────
    var scheduleToggle  = document.getElementById('mikro-btn-schedule-toggle');
    var scheduleRow     = document.getElementById('mikro-schedule-row');
    var btnSchedule     = document.getElementById('mikro-btn-schedule');
    var btnPublish      = document.getElementById('mikro-btn-publish');
    var scheduleActive  = false;

    if (scheduleToggle && scheduleRow && btnSchedule) {
        scheduleToggle.addEventListener('click', function () {
            scheduleActive = !scheduleActive;
            scheduleRow.hidden    = !scheduleActive;
            btnSchedule.hidden    = !scheduleActive;
            btnPublish.hidden     = scheduleActive;
            scheduleToggle.classList.toggle('is-active', scheduleActive);
            scheduleToggle.textContent = scheduleActive ? 'Avbryt' : 'Tidsinställ';
        });
    }

    // ── Formulärskickning via AJAX ───────────────────────────────────────────
    var form     = document.getElementById('mikro-write-form');
    var feedback = document.getElementById('mikro-write-feedback');

    if (!form || !window.blogtreeMikro) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var submitBtn = e.submitter || form.querySelector('[type="submit"]');
        var actionType = submitBtn ? submitBtn.value : 'publish';

        var content = textarea ? textarea.value.trim() : '';
        if (!content) {
            showFeedback('Skriv något innan du publicerar.', 'error');
            return;
        }
        if (content.length > MAX) {
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

        var data = new FormData(form);
        data.set('action',      'blogtree_save_mikro');
        data.set('nonce',       document.querySelector('[name="blogtree_mikro_nonce"]').value);
        data.set('action_type', actionType);

        setLoading(submitBtn, true);
        hideFeedback();

        fetch(blogtreeMikro.ajaxurl, {
            method: 'POST',
            body:   data,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                showFeedback(res.data.message, 'success');
                form.reset();
                if (counter) counter.textContent = '0';
                if (res.data.status === 'publish') {
                    setTimeout(function () {
                        window.location.href = res.data.permalink;
                    }, 1200);
                }
            } else {
                showFeedback(res.data.message || 'Något gick fel.', 'error');
            }
        })
        .catch(function () {
            showFeedback('Nätverksfel – försök igen.', 'error');
        })
        .finally(function () {
            setLoading(submitBtn, false);
        });
    });

    function showFeedback(msg, type) {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.className   = 'mikro-write-feedback mikro-write-feedback--' + type;
        feedback.hidden      = false;
    }

    function hideFeedback() {
        if (feedback) feedback.hidden = true;
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled    = loading;
        btn.textContent = loading ? 'Sparar…' : btn.dataset.label || btn.textContent;
    }
})();
