/**
 * konto.js – Kontosidans interaktivitet
 *
 * - Avatar-upload via AJAX med live-förhandsvisning
 * - Lösenordsbekräftelse-validering
 */
(function () {
    'use strict';

    // ── Avatar – live-förhandsvisning + AJAX-upload ──────────────────────────────
    var fileInput = document.getElementById('avatar_file');
    var avatarImg = document.querySelector('.konto-form__avatar-upload img');

    if (fileInput && avatarImg && window.blogtreeKonto) {

        // Visa förhandsvisning direkt när fil väljs
        fileInput.addEventListener('change', function () {
            var file = fileInput.files[0];
            if (!file) return;

            // Visa förhandsvisning lokalt
            var reader = new FileReader();
            reader.onload = function (e) {
                avatarImg.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Ladda upp via AJAX
            var form = new FormData();
            form.append('action', 'blogtree_upload_avatar');
            form.append('nonce', blogtreeKonto.avatarNonce);
            form.append('avatar', file);

            var btn = document.querySelector('.konto-form__avatar-btn');
            if (btn) {
                btn.textContent = 'Laddar upp…';
                btn.style.pointerEvents = 'none';
            }

            fetch(blogtreeKonto.ajaxurl, { method: 'POST', body: form })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        avatarImg.src = data.data.url;
                        showNotice('Profilbilden uppdaterad.', 'success');
                    } else {
                        showNotice(data.data || 'Uppladdning misslyckades.', 'error');
                    }
                })
                .catch(function () {
                    showNotice('Uppladdning misslyckades.', 'error');
                })
                .finally(function () {
                    if (btn) {
                        btn.textContent = 'Byt profilbild';
                        btn.style.pointerEvents = '';
                    }
                });
        });
    }

    // ── Lösenordsbekräftelse ────────────────────────────────────────────────────
    var pass1 = document.getElementById('pass1');
    var pass2 = document.getElementById('pass2');
    var form  = document.querySelector('.konto-form');

    if (form && pass1 && pass2) {
        form.addEventListener('submit', function (e) {
            if (pass1.value && pass1.value !== pass2.value) {
                e.preventDefault();
                pass2.setCustomValidity('Lösenorden matchar inte.');
                pass2.reportValidity();
            } else {
                pass2.setCustomValidity('');
            }
        });
        pass2.addEventListener('input', function () {
            pass2.setCustomValidity('');
        });
    }

    // ── Hjälpfunktion: notis ────────────────────────────────────────────────────
    function showNotice(message, type) {
        var existing = document.querySelector('.konto-notice--js');
        if (existing) existing.remove();

        var notice = document.createElement('div');
        notice.className = 'konto-notice konto-notice--' + type + ' konto-notice--js';
        notice.textContent = message;

        var section = document.querySelector('.konto-section');
        if (section) {
            section.insertBefore(notice, section.querySelector('.konto-form'));
        }

        setTimeout(function () { notice.remove(); }, 4000);
    }

}());
