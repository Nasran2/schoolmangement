<style>
    .owner-transfer-sent { background:#eef2ff; color:#4338ca; }
    .owner-transfer-returned { background:#f0fdf9; color:#047857; }
    .owner-transfer-list-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:20px; }
    .owner-transfer-legend { display:flex; flex-wrap:wrap; gap:16px; font-size:12px; color:#64748b; }
    .owner-transfer-legend > span { display:inline-flex; align-items:center; gap:6px; }
    .owner-transfer-dot { display:inline-block; width:7px; height:7px; border-radius:50%; }
    .owner-transfer-dot-sent { background:#818cf8; }
    .owner-transfer-dot-return { background:#34b399; }
    .owner-transfer-table-frame { border:1px solid #e2e8f0; border-radius:10px; }
    .owner-transfer-table { width:100%; border-collapse:separate; border-spacing:0; color:#334155; }
    .owner-transfer-table th { padding:13px 12px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:12px; font-weight:600; }
    .owner-transfer-table td { padding:16px 12px; vertical-align:middle; }
    .owner-transfer-group + .owner-transfer-group .owner-transfer-main-row td { border-top:1px solid #e2e8f0; }
    .owner-transfer-main-row { background:#fff; }
    .owner-transfer-date-cell { white-space:nowrap; }
    .owner-transfer-date { color:#334155; font-weight:500; }
    .owner-transfer-reference { color:#64748b; font-size:12px; margin-top:3px; }
    .owner-transfer-meta { color:#64748b; font-size:12px; line-height:1.5; margin-top:3px; overflow-wrap:anywhere; }
    .owner-transfer-destination-cell { min-width:160px; max-width:320px; }
    .owner-transfer-destination { font-weight:600; color:#1e293b; }
    .owner-transfer-amount { display:inline-block; padding:6px 10px; border-radius:7px; font-size:14px; font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .owner-transfer-amount-sent { background:#f5f5ff; color:#6366a5; }
    .owner-transfer-amount-return { background:#eefbf5; color:#047857; }
    .owner-transfer-amount-zero { background:#f8fafc; color:#64748b; font-weight:500; }
    .owner-transfer-amount-net { background:#eef2ff; color:#4338ca; font-weight:700; }
    .owner-transfer-actions { display:flex; align-items:center; flex-wrap:wrap; gap:8px; min-width:154px; }
    .owner-transfer-button { display:inline-flex; align-items:center; justify-content:center; min-height:34px; border:1px solid #e2e8f0; border-radius:7px; padding:6px 11px; font-size:12px; font-weight:500; color:#475569; background:#fff; white-space:nowrap; transition:background-color .15s,border-color .15s; }
    .owner-transfer-button:hover { background:#f8fafc; border-color:#cbd5e1; }
    .owner-transfer-button-return { color:#047857; border-color:#d1e9df; }
    .owner-transfer-button-return:hover { background:#eefbf5; border-color:#a7d8c2; }
    .owner-transfer-button-delete { color:#be4754; border-color:#f0dce0; }
    .owner-transfer-button-delete:hover { background:#fff1f2; border-color:#fecdd3; }
    .owner-transfer-button:focus-visible { outline:2px solid #6366f1; outline-offset:2px; }
    .owner-transfer-button:disabled { opacity:.6; cursor:wait; }
    .owner-transfer-return-row { background:#fafdfb; font-size:12px; }
    .owner-transfer-return-row td { padding-top:12px; padding-bottom:12px; border-top:1px solid #f1f5f3; }
    .owner-transfer-return-row td:first-child { border-left:3px solid #bbebd5; padding-left:13px; color:#64748b; }
    .owner-transfer-return-title { color:#527565; font-weight:600; }
    .owner-transfer-return-title > span { margin-right:5px; color:#6ba98c; }
    .owner-transfer-return-row .owner-transfer-amount { font-size:12px; }
    .owner-transfer-status { display:inline-block; margin-top:6px; border-radius:5px; padding:3px 7px; font-size:11px; font-weight:500; line-height:1.4; }
    .owner-transfer-status-returned { color:#527565; background:#eff7f2; }
    .owner-transfer-status-deleted { color:#b45360; background:#fce7eb; }
    .owner-transfer-deleted .owner-transfer-main-row, .owner-transfer-deleted .owner-transfer-return-row { background:#fff8f8; }
    .owner-transfer-deleted .owner-transfer-date, .owner-transfer-deleted .owner-transfer-destination, .owner-transfer-deleted .owner-transfer-return-title { color:#a64754; }
    .owner-transfer-deleted .owner-transfer-amount { color:#b45360; background:#fff0f2; }
    .owner-transfer-deleted .owner-transfer-meta, .owner-transfer-deleted .owner-transfer-reference { color:#9d6670; }
    .owner-transfer-deleted .owner-transfer-return-row td:first-child { border-left-color:#f2ccd3; color:#a64754; }
    .owner-transfer-deletion-reason { color:#a64754; font-size:12px; margin-top:5px; overflow-wrap:anywhere; }
    .owner-transfer-dialog { border:1px solid #e2e8f0; border-radius:14px; width:calc(100% - 32px); max-width:520px; max-height:90vh; padding:24px; overflow:auto; box-shadow:0 20px 60px #0f172a26; color:#334155; }
    .owner-transfer-dialog::backdrop { background:#0f172a66; }
    .owner-transfer-dialog input, .owner-transfer-dialog textarea { width:100%; margin-top:5px; border:1px solid #dbe2ea; border-radius:7px; }
    .owner-transfer-dialog label { display:block; font-size:14px; color:#475569; }
    .owner-transfer-dialog form > div { margin-top:16px; }
    .owner-transfer-dialog-header { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .owner-transfer-dialog-header h3 { font-size:20px; font-weight:600; color:#1e293b; }
    .owner-transfer-dialog-actions { display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap; }
    @media (max-width:639px) { .owner-transfer-table th, .owner-transfer-table td { padding-left:12px; padding-right:12px; } .owner-transfer-return-row td:first-child { padding-left:9px; } .owner-transfer-dialog { padding:20px; } }
</style>
