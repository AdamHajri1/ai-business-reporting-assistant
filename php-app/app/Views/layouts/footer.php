</main>

<div id="confirmModal" class="modal-backdrop no-print" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" hidden>
    <div class="modal-card">
        <h3 id="confirmModalTitle"><?= htmlspecialchars(I18n::t('report.delete_modal_title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="confirmModalMessage" class="subtitle"></p>
        <div class="modal-actions">
            <button type="button" id="confirmModalCancel" class="btn btn-secondary">
                <?= htmlspecialchars(I18n::t('report.cancel_btn'), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" id="confirmModalConfirm" class="btn btn-danger">
                <?= htmlspecialchars(I18n::t('report.delete_btn'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    // Custom CSV dropzone widget (localized & drag-and-drop)
    const fileInput = document.getElementById('csv_file');
    const dropzone = document.getElementById('csvDropzone');
    const titleEl = document.getElementById('csvFileTitle');
    const metaEl = document.getElementById('csvFileMeta');

    if (fileInput && dropzone && titleEl && metaEl) {
        const defaultTitle = dropzone.getAttribute('data-default-title') || '';
        const defaultMeta = dropzone.getAttribute('data-default-meta') || '';

        function updateFileUI(files) {
            if (files && files.length > 0) {
                const f = files[0];
                titleEl.textContent = f.name;
                metaEl.textContent = (f.size / 1024).toFixed(1) + ' KB';
                dropzone.classList.add('has-file');
            } else {
                titleEl.textContent = defaultTitle;
                metaEl.textContent = defaultMeta;
                dropzone.classList.remove('has-file');
            }
        }

        fileInput.addEventListener('change', function () {
            updateFileUI(fileInput.files);
        });

        ['dragenter', 'dragover'].forEach(function (evtName) {
            dropzone.addEventListener(evtName, function (e) {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (evtName) {
            dropzone.addEventListener(evtName, function (e) {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileUI(fileInput.files);
            }
        });
    }

    // In-page confirmation modal + AJAX delete
    const modal = document.getElementById('confirmModal');
    const modalTitleEl = document.getElementById('confirmModalTitle');
    const messageEl = document.getElementById('confirmModalMessage');
    const cancelBtn = document.getElementById('confirmModalCancel');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    if (!modal || !modalTitleEl || !messageEl || !cancelBtn || !confirmBtn) return;

    const defaultModalTitle = modalTitleEl.textContent || '';
    let pendingForm = null;

    function closeModal() {
        modal.hidden = true;
        pendingForm = null;
    }

    function replaceTableWithEmptyState(card) {
        if (!card) return;
        const tbody = card.querySelector('tbody');
        if (tbody && tbody.children.length === 0) {
            const tableWrap = card.querySelector('.table-wrap');
            const emptyText = card.getAttribute('data-empty-message') || '';
            if (tableWrap && emptyText) {
                const p = document.createElement('p');
                p.className = 'subtitle';
                p.textContent = emptyText;
                tableWrap.replaceWith(p);
            }
        }
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const msg = form.getAttribute('data-confirm');
        if (!msg) return;

        event.preventDefault();
        pendingForm = form;
        modalTitleEl.textContent = form.getAttribute('data-confirm-title') || defaultModalTitle;
        messageEl.textContent = msg;
        modal.hidden = false;
        cancelBtn.focus();
    });

    cancelBtn.addEventListener('click', closeModal);

    function showInlineAlert(card, type, text) {
        if (!card || !text) return;
        let banner = card.querySelector('.ajax-alert');
        if (!banner) {
            banner = document.createElement('div');
            banner.className = 'alert ajax-alert';
            const heading = card.querySelector('h2');
            if (heading && heading.nextSibling) {
                card.insertBefore(banner, heading.nextSibling);
            } else {
                card.prepend(banner);
            }
        }
        banner.className = 'alert ajax-alert ' + (type === 'error' ? 'alert-error' : 'alert-success');
        banner.setAttribute('role', type === 'error' ? 'alert' : 'status');
        banner.textContent = text;
    }

    confirmBtn.addEventListener('click', async function () {
        if (!pendingForm) return;
        const formToSubmit = pendingForm;

        if (!formToSubmit.hasAttribute('data-ajax-delete')) {
            closeModal();
            formToSubmit.submit();
            return;
        }

        confirmBtn.disabled = true;
        try {
            const response = await fetch(formToSubmit.action, {
                method: 'POST',
                body: new FormData(formToSubmit),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            const card = formToSubmit.closest('.card');

            if (!response.ok || !data.ok) {
                closeModal();
                showInlineAlert(card, 'error', data.error || 'Failed to delete.');
                return;
            }

            const rowSelector = formToSubmit.getAttribute('data-ajax-delete') || 'tr';
            const row = formToSubmit.closest(rowSelector);
            if (row) row.remove();
            replaceTableWithEmptyState(card);

            // If a dataset was deleted, also remove its cascaded report rows from the reports card
            if (data.dataset_id) {
                const reportsCard = document.getElementById('reportsCard');
                if (reportsCard) {
                    reportsCard.querySelectorAll('tbody tr[data-dataset-id]').forEach(function (tr) {
                        if (tr.getAttribute('data-dataset-id') === data.dataset_id) {
                            tr.remove();
                        }
                    });
                    replaceTableWithEmptyState(reportsCard);
                }
            }

            closeModal();
            showInlineAlert(card, 'success', data.message || '');
        } catch (err) {
            closeModal();
            formToSubmit.submit();
        } finally {
            confirmBtn.disabled = false;
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>