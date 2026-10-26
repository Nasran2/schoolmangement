<style>
    .fee-register-compact { padding:14px 0 24px; margin:0 auto; max-width:100%; }
    .fee-register-view-tabs { display:flex; flex-wrap:wrap; gap:12px; margin:3px 0 0; font-size:12px; }
    .fee-register-view-tabs > * { padding:0; }
    .fee-register-filters { padding:12px; background:#fff; border:1px solid #e2e8f0; border-radius:9px; }
    .fee-register-filter-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:9px; }
    .fee-register-filter-grid > div > label { display:block; font-size:12px; color:#475569; font-weight:500; margin-bottom:4px; }
    .fee-register-filter-grid input, .fee-register-filter-grid select { width:100%; min-width:0; height:35px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; }
    .fee-register-filter-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:8px; margin-top:10px; }
    .fee-register-filter-options, .fee-register-filter-downloads { display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; }
    .fee-register-filter-options label { display:flex; align-items:center; gap:6px; font-size:11px; color:#475569; }
    .fee-register-filters input[type="checkbox"] { width:14px; height:14px; border:1px solid #cbd5e1; border-radius:3px; color:#4f46e5; flex:none; }
    .fee-register-term-row { display:flex; align-items:center; gap:8px; margin-top:10px; padding-top:10px; border-top:1px solid #edf0f4; overflow-x:auto; white-space:nowrap; }
    .fee-register-term-caption { font-size:12px; color:#64748b; }
    .fee-register-term-chip { display:inline-flex; flex:none; align-items:center; gap:6px; padding:5px 8px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc; font-size:12px; color:#475569; cursor:pointer; }
    .fee-register-term-link { flex:none; color:#4338ca; font-size:11px; text-decoration:underline; }
    .fee-register-result-count { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px; margin:5px 0 8px; font-size:12px; color:#64748b; }
    .fee-register-results > section + section { margin-top:12px; }
    .fee-register-compact > .fee-register-loading { margin-top:6px; }
    @media(min-width:640px) { .fee-register-filter-grid { grid-template-columns:minmax(0,1fr) 75px minmax(0,1fr) minmax(0,1.2fr) 90px; } }
    @media(max-width:639px) { .fee-register-filter-grid { grid-template-columns:minmax(0,1fr) 90px; } .fee-register-filter-grid > div:nth-child(3), .fee-register-filter-grid > div:nth-child(4) { grid-column:1/-1; } .fee-register-option-detail { display:none; } }

    .fee-register-loading { min-height:18px; color:#64748b; font-size:12px; }
    [data-register-results][aria-busy="true"] { opacity:.6; }
    .fee-register-section { border:1px solid #e2e8f0; border-radius:10px; background:#fff; overflow:hidden; }
    .fee-register-section-title { padding:9px 12px; border-bottom:1px solid #e2e8f0; font-size:16px; font-weight:600; color:#1e293b; }
    .fee-register-table { width:100%; border-collapse:collapse; font-size:13px; }
    .fee-register-table th { background:#f8fafc; text-align:left; padding:8px 10px; color:#64748b; font-size:12px; font-weight:600; min-width:150px; vertical-align:top; }
    .fee-register-table td > .fee-register-status-line { display:flex; flex-wrap:wrap; align-items:baseline; gap:4px 7px; }
    .fee-register-table td > .fee-register-status-line .fee-register-sub { margin-top:0; }
    .fee-register-table th:first-child { min-width:180px; }
    .fee-register-table td { padding:7px 10px; border-top:1px solid #edf0f4; vertical-align:top; }
    .fee-register-sub { margin-top:4px; font-size:11px; color:#64748b; line-height:1.5; }
    .fee-register-actions { display:flex; flex-wrap:wrap; gap:5px; margin-top:5px; }
    .fee-register-button { display:inline-flex; align-items:center; justify-content:center; background:#fff; border:1px solid #dbe2ea; border-radius:6px; padding:6px 9px; color:#4338ca; font-size:12px; font-weight:600; cursor:pointer; }
    .fee-register-button:hover { background:#f8fafc; }
    .fee-register-button:disabled { opacity:.5; cursor:wait; }
    .fee-register-unpaid { color:#b91c1c; border-color:#fecdd3; }
    .fee-register-submit { background:#4f46e5; color:#fff; border:0; border-radius:7px; padding:11px 18px; font-size:14px; font-weight:600; }
    .fee-register-submit:disabled { opacity:.5; cursor:wait; }
    .fee-register-submit-danger { background:#b91c1c; }
    .fee-register-dialog { width:calc(100% - 28px); max-width:680px; max-height:90vh; border:1px solid #e2e8f0; border-radius:12px; padding:24px; box-shadow:0 20px 60px #0f172a26; }
    .fee-register-dialog::backdrop { background:#0f172a66; }
    .fee-register-dialog-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
    .fee-register-dialog-header h2 { font-size:20px; font-weight:600; color:#1e293b; }
    .fee-register-dialog label { display:block; font-size:13px; font-weight:500; color:#475569; }
    .fee-register-dialog input:not([type="checkbox"]):not([type="hidden"]), .fee-register-dialog select, .fee-register-dialog textarea { width:100%; margin-top:5px; border:1px solid #cbd5e1; border-radius:6px; }
    .fee-register-fields { display:grid; grid-template-columns:minmax(0,1fr); gap:16px; margin-top:16px; }
    .fee-register-notice { background:#eefbf5; border:1px solid #cceadc; color:#166534; border-radius:8px; padding:14px; font-size:13px; }
    .fee-register-error { color:#b91c1c; background:#fff1f2; border:1px solid #fecdd3; padding:12px; border-radius:7px; font-size:13px; margin-top:12px; }
    .fee-register-context { background:#f8fafc; border:1px solid #e2e8f0; border-radius:7px; padding:12px; font-size:13px; color:#475569; }
    .fee-register-receipts { margin:12px 0; padding:0; list-style:none; }
    .fee-register-receipts li { padding:9px 0; border-bottom:1px solid #edf0f4; font-size:12px; color:#475569; }
    .fee-register-page-links { display:flex; flex-wrap:wrap; align-items:center; gap:5px; }
    .fee-register-page-current { background:#4f46e5; border-color:#4f46e5; color:white; }
    .fee-register-page-disabled { color:#94a3b8; background:#f8fafc; cursor:default; }
    .fee-register-page-gap { padding:0 3px; }
    .fee-register-page-summary { font-size:12px; color:#64748b; }
    .fee-register-pagination { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:10px 0; font-size:12px; color:#64748b; }
    [hidden] { display:none !important; }
    @media(min-width:640px) { .fee-register-fields { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
