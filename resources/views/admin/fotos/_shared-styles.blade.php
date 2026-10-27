{{-- Buttons, lightbox and toasts shared by the Foto Queue and its Archived tab. --}}
<style>
    .fq-btn { flex: 1; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 12px; border: 1px solid transparent; border-radius: 9px; font: 600 13px 'DM Sans', sans-serif; white-space: nowrap; cursor: pointer; transition: background .15s, border-color .15s, color .15s, transform .08s, filter .15s; }
    .fq-btn:active { transform: scale(.96); }
    .fq-btn:focus-visible { outline: 2px solid var(--fq-accent); outline-offset: 2px; }
    .fq-btn .lucide-icon { width: 15px; height: 15px; flex-shrink: 0; }
    .fq-btn-approve { background: var(--green); color: #04220f; }
    .fq-btn-approve:hover { filter: brightness(1.1); }
    .fq-btn-reject { background: rgba(255, 255, 255, .04); border-color: var(--border); color: var(--text); }
    .fq-btn-reject:hover { border-color: #f59e0b; color: #fbbf24; background: rgba(245, 158, 11, .08); }
    .fq-btn-delete { background: rgba(239, 68, 68, .08); border-color: rgba(239, 68, 68, .22); color: #f87171; }
    .fq-btn-delete:hover { background: rgba(239, 68, 68, .16); border-color: rgba(239, 68, 68, .45); }
    .fq-btn-delete.is-armed { background: #dc2626; border-color: #dc2626; color: #fff; }
    .fq-btn-icon { flex: 0 0 36px; padding: 0; }
    .fq-btn-icon .fq-btn-label { display: none; }
    .fq-btn-icon.is-armed { flex: 0 0 auto; padding: 0 12px; }
    .fq-btn-icon.is-armed .fq-btn-label { display: inline; }
    .fq-btn kbd { margin-left: 2px; padding: 2px 5px; border-radius: 4px; border: 1px solid currentColor; font: 700 10px/1 'DM Sans', sans-serif; opacity: .55; }

    .fq-spinner { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--border); border-top-color: var(--fq-accent); animation: fqSpin .7s linear infinite; }
    @keyframes fqSpin { to { transform: rotate(360deg); } }

    .fq-lb { position: fixed; inset: 0; z-index: 1000; display: flex; flex-direction: column; background: rgba(5, 5, 12, .95); backdrop-filter: blur(8px); opacity: 0; transition: opacity .15s; }
    .fq-lb.is-open { opacity: 1; }
    .fq-lb-top { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums; }
    .fq-lb-stage { position: relative; flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; padding: 0 76px; }
    .fq-lb-stage img, .fq-lb-stage video { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 10px; box-shadow: 0 24px 70px rgba(0, 0, 0, .6); background: #000; }
    .fq-lb-round { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: rgba(26, 26, 46, .85); color: var(--text); cursor: pointer; transition: background .15s, opacity .15s; }
    .fq-lb-round:hover:not(:disabled) { background: var(--border); }
    .fq-lb-round:disabled { opacity: .25; cursor: default; }
    .fq-lb-round .lucide-icon { width: 20px; height: 20px; }
    .fq-lb-prev, .fq-lb-next { position: absolute; top: 50%; transform: translateY(-50%); }
    .fq-lb-prev { left: 18px; }
    .fq-lb-next { right: 18px; }
    .fq-lb-bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; max-width: 960px; margin: 0 auto; padding: 18px 24px 24px; }
    .fq-lb-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 19px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fq-lb-meta { margin-top: 2px; font-size: 12px; color: var(--muted); }
    .fq-lb-info { min-width: 0; }
    .fq-lb-actions { display: flex; gap: 10px; flex-shrink: 0; }
    .fq-lb-actions .fq-btn { flex: none; min-width: 124px; height: 44px; font-size: 14px; }

    .fq-toasts { position: fixed; right: 20px; bottom: 20px; z-index: 1100; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; pointer-events: none; }
    .fq-toast { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--card2); font-size: 13px; box-shadow: 0 12px 30px rgba(0, 0, 0, .45); animation: fqToastIn .2s ease-out; transition: opacity .2s, transform .2s; }
    .fq-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #fca5a5; }
    .fq-toast.is-out { opacity: 0; transform: translateY(6px); }
    .fq-offline { display: inline-flex; align-items: center; gap: 8px; }
    .fq-offline .fq-spinner { width: 14px; height: 14px; }
    @keyframes fqToastIn { from { opacity: 0; transform: translateY(8px); } }

    @media (max-width: 640px) {
        .fq-btn { padding: 0 8px; }
        .fq-btn:not(.is-armed) .fq-btn-label { display: none; }
        .fq-btn kbd { display: none; }
        .fq-lb-stage { padding: 0 10px; }
        .fq-lb-prev, .fq-lb-next { display: none; }
        .fq-lb-bar { flex-direction: column; align-items: stretch; padding: 14px 14px 20px; }
        .fq-lb-actions .fq-btn { flex: 1; min-width: 0; }
        .fq-lb-actions .fq-btn-label { display: inline; }
        .fq-toasts { left: 16px; right: 16px; align-items: stretch; }
    }
</style>
