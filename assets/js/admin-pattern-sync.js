/**
 * Pattern Sync UI — toggle, copy embed, manage sync modal.
 *
 * @package AWBStarter
 */
(function () {
    'use strict';

    var cfg = window.awbPatternSync;
    if (!cfg) return;

    var restBase = cfg.restUrl;

    // ── Helpers ──────────────────────────────────────────────────────

    function api(method, path, body) {
        var opts = {
            method: method,
            headers: { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' }
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(restBase + path, opts).then(function (r) {
            return r.json().then(function (data) {
                return r.ok ? data : Promise.reject(data);
            });
        });
    }

    function show(el) { if (el) el.removeAttribute('hidden'); }
    function hide(el) { if (el) el.setAttribute('hidden', ''); }

    // ── Sync Toggle ─────────────────────────────────────────────────

    document.addEventListener('change', function (e) {
        var input = e.target.closest('.awb-sync-toggle__input');
        if (!input) return;

        var name = input.dataset.name;
        var checked = input.checked;

        api('POST', 'patterns/' + name + '/sync', { synced: checked })
            .then(function () {
                // Update card overlay buttons
                var card = input.closest('.awb-pattern-card');
                if (!card) return;

                var embedBtn = card.querySelector('.awb-copy-synced');
                var manageBtn = card.querySelector('.awb-manage-sync');

                if (checked) {
                    if (!embedBtn) {
                        var footer = card.querySelector('.awb-pattern-card__footer');
                        if (footer) {
                            var copyBtn = document.createElement('button');
                            copyBtn.className = 'awb-btn awb-btn--ghost awb-btn--sm awb-copy-synced';
                            copyBtn.dataset.name = name;
                            copyBtn.title = 'Copy synced embed';
                            copyBtn.innerHTML = '<i class="fas fa-link"></i> Embed';
                            footer.appendChild(copyBtn);
                        }
                    }
                    if (!manageBtn) {
                        var footer2 = card.querySelector('.awb-pattern-card__footer');
                        if (footer2) {
                            var title = card.querySelector('.awb-pattern-card__title');
                            var syncBtn = document.createElement('button');
                            syncBtn.className = 'awb-btn awb-btn--ghost awb-btn--sm awb-manage-sync';
                            syncBtn.dataset.name = name;
                            syncBtn.dataset.title = title ? title.textContent : '';
                            syncBtn.title = 'Sync pages';
                            syncBtn.innerHTML = '<i class="fas fa-sync"></i> Sync';
                            footer2.appendChild(syncBtn);
                        }
                    }
                } else {
                    if (embedBtn) embedBtn.remove();
                    if (manageBtn) manageBtn.remove();
                }

                var label = input.closest('.awb-sync-toggle');
                if (label) {
                    label.title = checked ? 'Sync is ON' : 'Sync is OFF';
                }
            })
            .catch(function (err) {
                input.checked = !checked;
                console.error('Sync toggle failed:', err);
            });
    });

    // ── Copy Synced Embed (overlay + modal) ─────────────────────────

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.awb-copy-synced, .awb-sync-copy-embed');
        if (!btn) return;

        var name = btn.dataset.name || btn.dataset.name;
        if (!name) return;

        var markup = '<!-- wp:pattern {"slug":"' + name + '"} /-->';
        navigator.clipboard.writeText(markup).then(function () {
            var orig = btn.textContent;
            btn.textContent = cfg.i18n.copied || 'Copied!';
            setTimeout(function () { btn.textContent = orig; }, 1500);
        });
    });

    // ── Copy Pattern Markup ─────────────────────────────────────────

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.awb-copy-pattern');
        if (!btn) return;
        var content = btn.dataset.content;
        if (!content) return;
        navigator.clipboard.writeText(content).then(function () {
            var orig = btn.textContent;
            btn.textContent = cfg.i18n.copied || 'Copied!';
            setTimeout(function () { btn.textContent = orig; }, 1500);
        });
    });

    // ── Manage Sync Modal ───────────────────────────────────────────

    var syncModal     = document.getElementById('awb-sync-modal');
    var syncBackdrop  = document.getElementById('awb-sync-modal-backdrop');
    var syncClose     = document.getElementById('awb-sync-modal-close');
    var syncBody      = document.getElementById('awb-sync-modal-body');
    var syncList      = document.getElementById('awb-sync-modal-list');
    var syncEmpty     = document.getElementById('awb-sync-modal-empty');
    var syncDone      = document.getElementById('awb-sync-modal-done');
    var syncLoading   = syncBody ? syncBody.querySelector('.awb-sync-modal__loading') : null;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.awb-manage-sync');
        if (!btn || !syncModal) return;

        var name  = btn.dataset.name;
        var title = btn.dataset.title || '';
        syncModal.dataset.name = name;

        var titleEl = syncModal.querySelector('#awb-sync-modal-title');
        if (titleEl) titleEl.textContent = 'Sync pages — ' + title;

        // Reset and show loading
        syncList.innerHTML = '';
        hide(syncList);
        hide(syncEmpty);
        show(syncLoading);
        syncModal.removeAttribute('hidden');

        // Fetch usages
        api('GET', 'patterns/' + name + '/sync/usages')
            .then(function (data) {
                hide(syncLoading);
                var usages = data.usages || [];
                if (!usages.length) {
                    show(syncEmpty);
                    return;
                }
                usages.forEach(function (u) {
                    var li = document.createElement('li');
                    li.className = 'awb-sync-modal__item';
                    if (u.already_synced) li.classList.add('is-synced');
                    if (u.drifted) li.classList.add('is-drifted');

                    var info = document.createElement('span');
                    info.className = 'awb-sync-modal__item-info';
                    info.innerHTML = '<strong>' + escHtml(u.title) + '</strong><small>' + escHtml(u.name) + '</small>';
                    li.appendChild(info);

                    var status = document.createElement('span');
                    status.className = 'awb-sync-modal__item-status';
                    if (u.already_synced) {
                        status.textContent = cfg.i18n.alreadySynced || 'Synced';
                        status.classList.add('is-synced');
                    } else if (u.drifted) {
                        var driftBadge = document.createElement('span');
                        driftBadge.className = 'awb-sync-modal__drift-badge';
                        driftBadge.textContent = '⚠ ' + (u.diff_summary || 'Content differs');
                        status.appendChild(driftBadge);

                        var convertBtn = document.createElement('button');
                        convertBtn.className = 'awb-btn awb-btn--small awb-btn--primary awb-sync-convert';
                        convertBtn.dataset.postId = u.id;
                        convertBtn.dataset.name = name;
                        convertBtn.dataset.drifted = 'true';
                        convertBtn.dataset.diffSummary = u.diff_summary || '';
                        convertBtn.textContent = cfg.i18n.syncAnyway || 'Sync anyway';
                        status.appendChild(convertBtn);
                    } else {
                        var convertBtn = document.createElement('button');
                        convertBtn.className = 'awb-btn awb-btn--small awb-btn--primary awb-sync-convert';
                        convertBtn.dataset.postId = u.id;
                        convertBtn.dataset.name = name;
                        convertBtn.textContent = cfg.i18n.convert || 'Convert';
                        status.appendChild(convertBtn);
                    }
                    li.appendChild(status);
                    syncList.appendChild(li);
                });
                show(syncList);
            })
            .catch(function (err) {
                hide(syncLoading);
                show(syncEmpty);
                console.error('Fetch usages failed:', err);
            });
    });

    // Close sync modal
    if (syncClose) syncClose.addEventListener('click', function () { hide(syncModal); });
    if (syncBackdrop) syncBackdrop.addEventListener('click', function () { hide(syncModal); });
    if (syncDone) syncDone.addEventListener('click', function () { hide(syncModal); });

    // ── Convert a page to synced ────────────────────────────────────

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.awb-sync-convert');
        if (!btn) return;

        var name   = btn.dataset.name;
        var postId = parseInt(btn.dataset.postId, 10);
        var isDrifted = btn.dataset.drifted === 'true';
        var diffSummary = btn.dataset.diffSummary || '';

        var confirmMsg;
        if (isDrifted) {
            confirmMsg = (cfg.i18n.driftConfirm || 'This page has custom edits (%s) that differ from the pattern source. Converting will overwrite those edits with the current pattern content. A revision backup will be created. Continue?').replace('%s', diffSummary);
        } else {
            confirmMsg = cfg.i18n.convertConfirm || 'This will replace the page content with a synced reference. A revision backup will be created. Continue?';
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        btn.disabled = true;
        btn.textContent = cfg.i18n.converting || 'Converting…';

        api('POST', 'patterns/' + name + '/sync/convert', { post_id: postId })
            .then(function () {
                var item = btn.closest('.awb-sync-modal__item');
                if (item) {
                    item.classList.add('is-synced');
                    var status = item.querySelector('.awb-sync-modal__item-status');
                    if (status) {
                        status.innerHTML = '';
                        var span = document.createElement('span');
                        span.className = 'awb-sync-modal__item-status is-synced';
                        span.textContent = cfg.i18n.alreadySynced || 'Synced';
                        status.appendChild(span);
                    }
                }
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.textContent = cfg.i18n.convert || 'Convert';
                console.error('Convert failed:', err);
                alert(err.message || cfg.i18n.convertError || 'Conversion failed.');
            });
    });

    // ── Preview Modal (existing) ────────────────────────────────────

    var previewModal    = document.getElementById('awb-preview-modal');
    var previewBackdrop = document.getElementById('awb-modal-backdrop');
    var previewClose    = document.getElementById('awb-modal-close');
    var previewCloseBtn = document.getElementById('awb-modal-close-btn');
    var previewBody     = document.getElementById('awb-modal-body');
    var previewCopy     = document.getElementById('awb-modal-copy');

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.awb-insert-pattern');
        if (!btn || !previewModal) return;

        var name = btn.dataset.slug;
        previewCopy.dataset.name = name;

        var iframe = document.createElement('iframe');
        iframe.src = cfg.adminAjax + '?action=awb_preview_pattern&nonce=' + cfg.previewNonce + '&pattern=' + encodeURIComponent(name);
        iframe.className = 'awb-preview-iframe';
        previewBody.innerHTML = '';
        previewBody.appendChild(iframe);
        previewModal.removeAttribute('hidden');
    });

    if (previewClose) previewClose.addEventListener('click', function () { hide(previewModal); previewBody.innerHTML = ''; });
    if (previewBackdrop) previewBackdrop.addEventListener('click', function () { hide(previewModal); previewBody.innerHTML = ''; });
    if (previewCloseBtn) previewCloseBtn.addEventListener('click', function () { hide(previewModal); previewBody.innerHTML = ''; });
    if (previewCopy) {
        previewCopy.addEventListener('click', function () {
            var content = previewCopy.dataset.content;
            if (content) navigator.clipboard.writeText(content);
        });
    }

    // ── Utilities ───────────────────────────────────────────────────

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})();
