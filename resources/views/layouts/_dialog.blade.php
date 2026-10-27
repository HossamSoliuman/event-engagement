{{--
    The in-app confirm/alert dialog for the admin and moderator layouts. Never use the browser's confirm() or alert().
    Forms or submit buttons ask first with data-confirm="Question?" plus optional data-confirm-message,
    data-confirm-button="Delete" and data-confirm-danger. Scripts call appConfirm(title, options) or appAlert(title, options).
--}}
<style>
    .app-dialog { position: fixed; inset: 0; margin: auto; height: fit-content; max-height: calc(100vh - 32px); width: min(440px, calc(100vw - 32px)); padding: 0; border: 1px solid var(--border); border-radius: 16px; background: var(--card2); color: var(--text); box-shadow: 0 30px 80px rgba(0, 0, 0, .55); }
    .app-dialog::backdrop { background: rgba(5, 5, 12, .72); backdrop-filter: blur(4px); }
    .app-dialog[open] { animation: appDialogIn .16s ease-out; }
    @keyframes appDialogIn { from { opacity: 0; transform: translateY(8px) scale(.98); } }
    .app-dialog-box { padding: 22px 22px 18px; }
    .app-dialog-title { margin: 0; font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 700; line-height: 1.35; }
    .app-dialog-message { margin: 8px 0 0; color: var(--muted); font-size: 13.5px; line-height: 1.55; }
    .app-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .app-dialog-actions button { height: 40px; padding: 0 18px; border-radius: 10px; border: 1px solid var(--border); font: 600 13.5px 'DM Sans', sans-serif; cursor: pointer; transition: filter .15s, background .15s; }
    .app-dialog-cancel { background: transparent; color: var(--text); }
    .app-dialog-cancel:hover { background: rgba(255, 255, 255, .05); }
    .app-dialog-ok { background: var(--mod, var(--red)); border-color: transparent !important; color: #fff; }
    .app-dialog-ok.is-danger { background: #dc2626; }
    .app-dialog-ok:hover { filter: brightness(1.1); }
    .app-dialog-actions button:focus-visible { outline: 2px solid var(--mod, var(--red)); outline-offset: 2px; }
    @media (max-width: 480px) {
        .app-dialog-actions button { flex: 1; }
    }
</style>

<dialog class="app-dialog" id="appDialog" aria-labelledby="appDialogTitle">
    <div class="app-dialog-box">
        <h2 class="app-dialog-title" id="appDialogTitle"></h2>
        <p class="app-dialog-message" id="appDialogMessage" hidden></p>
        <div class="app-dialog-actions">
            <button type="button" class="app-dialog-cancel" id="appDialogCancel">Cancel</button>
            <button type="button" class="app-dialog-ok" id="appDialogOk">Confirm</button>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.getElementById('appDialog');
        const title = document.getElementById('appDialogTitle');
        const message = document.getElementById('appDialogMessage');
        const ok = document.getElementById('appDialogOk');
        const cancel = document.getElementById('appDialogCancel');
        let settle = null;

        function open(text, { message: body = '', confirmLabel = 'Confirm', cancelLabel = 'Cancel', danger = false, alertOnly = false } = {}) {
            settle?.(false);
            title.textContent = text;
            message.textContent = body;
            message.hidden = !body;
            ok.textContent = confirmLabel;
            ok.classList.toggle('is-danger', danger);
            cancel.textContent = cancelLabel;
            cancel.hidden = alertOnly;
            if (!dialog.open) dialog.showModal();
            (danger ? cancel : ok).focus();

            return new Promise(resolve => { settle = resolve; });
        }

        function close(result) {
            const resolve = settle;
            settle = null;
            if (dialog.open) dialog.close();
            resolve?.(result);
        }

        ok.addEventListener('click', () => close(true));
        cancel.addEventListener('click', () => close(false));
        dialog.addEventListener('cancel', event => { event.preventDefault(); close(false); });
        dialog.addEventListener('click', event => { if (event.target === dialog) close(false); });

        /** Resolves true when confirmed, false when cancelled. */
        window.appConfirm = (text, options = {}) => open(text, options);

        /** A message with a single OK button; resolves when it is closed. */
        window.appAlert = (text, options = {}) => open(text, { confirmLabel: 'OK', ...options, alertOnly: true }).then(() => undefined);

        document.addEventListener('submit', event => {
            const form = event.target;
            const submitter = event.submitter;
            const source = submitter?.dataset.confirm ? submitter : (form.dataset.confirm ? form : null);

            if (!source) {
                return;
            }
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            open(source.dataset.confirm, {
                message: source.dataset.confirmMessage || '',
                confirmLabel: source.dataset.confirmButton || 'Confirm',
                danger: 'confirmDanger' in source.dataset,
            }).then(confirmed => {
                if (!confirmed) {
                    return;
                }
                form.dataset.confirmed = '1';
                form.requestSubmit(submitter?.form === form ? submitter : undefined);
            });
        }, true);
    })();
</script>
