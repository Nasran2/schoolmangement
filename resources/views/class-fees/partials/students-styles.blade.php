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
    .fee-register-section-title { display:flex; flex-wrap:wrap; align-items:center; gap:6px 14px; padding:10px 12px; border-bottom:1px solid #e2e8f0; font-size:16px; font-weight:600; color:#1e293b; }
    .fee-register-section-title span { font-size:11px; font-weight:400; color:#64748b; }
    .fee-register-table-scroll { overflow-x:auto; }
    .fee-register-table-scroll:focus-visible { outline:2px solid #818cf8; outline-offset:-2px; }
    .fee-register-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
    .fee-register-table th, .fee-register-table td { text-align:left; padding:10px; vertical-align:top; }
    .fee-register-table thead th { background:#f8fafc; color:#475569; font-size:12px; font-weight:600; border-bottom:1px solid #e2e8f0; }
    .fee-register-table tbody td, .fee-register-table tbody th { border-bottom:1px solid #e2e8f0; background:#fff; }
    .fee-register-table tbody tr:last-child > * { border-bottom:0; }
    .fee-register-table tbody tr:nth-child(even) > * { background:#fafbfc; }
    .fee-register-table tbody tr:hover > * { background:#f8faff; }
    .fee-register-table .fee-register-student-column { min-width:145px; width:15%; position:sticky; left:0; z-index:1; }
    .fee-register-table .fee-register-term-column { min-width:175px; border-left:1px solid #edf0f4; }
    .fee-register-table .fee-register-facility-column { min-width:155px; border-left:2px solid #e2e8f0; }
    .fee-register-table .fee-register-due-column { min-width:100px; text-align:right; border-left:1px solid #e2e8f0; background:#f6f7fc; }
    .fee-register-student-name { font-weight:600; color:#1e293b; line-height:1.5; }
    .fee-register-column-title { font-size:13px; color:#334155; }
    .fee-register-column-title span { color:#64748b; font-size:11px; font-weight:400; }
    .fee-register-rates { display:flex; flex-direction:column; gap:2px; margin-top:5px; font-size:10px; color:#64748b; font-weight:400; }
    .fee-register-rates strong { color:#475569; font-weight:500; }
    .fee-register-fee + .fee-register-fee { border-top:1px dashed #e2e8f0; margin-top:8px; padding-top:8px; }
    .fee-register-fee-heading { display:flex; align-items:center; justify-content:space-between; gap:7px; font-size:11px; font-weight:600; color:#334155; }
    .fee-register-badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; line-height:1.4; font-weight:500; white-space:nowrap; }
    .fee-register-badge-paid { background:#ecfdf3; color:#166534; }
    .fee-register-badge-due { background:#fff1f2; color:#b91c1c; }
    .fee-register-badge-pending { background:#fffbeb; color:#92400e; }
    .fee-register-badge-muted { background:#f1f5f9; color:#64748b; }
    .fee-register-fee-amounts { display:flex; align-items:baseline; justify-content:space-between; flex-wrap:wrap; gap:4px 8px; margin-top:4px; font-size:10px; color:#64748b; }
    .fee-register-fee-amounts strong { font-size:11px; color:#334155; font-weight:500; font-variant-numeric:tabular-nums; }
    .fee-register-inline-actions { display:flex; align-items:center; flex-wrap:wrap; gap:6px; }
    .fee-register-pay-link { color:#4338ca; font-size:11px; font-weight:600; text-decoration:underline; text-underline-offset:2px; }
    .fee-register-correction-link { color:#64748b; font-size:10px; text-decoration:underline; text-underline-offset:2px; }
    .fee-register-correction-link:hover { color:#b91c1c; }
    .fee-register-payment-details { margin-top:4px; font-size:10px; color:#64748b; }
    .fee-register-payment-details summary { cursor:pointer; }
    .fee-register-receipt-link { padding:5px 0; overflow-wrap:anywhere; }
    .fee-register-receipt-link a { color:#4338ca; text-decoration:underline; }
    .fee-register-combined-pay { display:block; margin-top:8px; padding:5px 8px; border:1px solid #d9ddfc; border-radius:5px; background:#eef2ff; color:#4338ca; font-size:11px; font-weight:600; text-align:center; }
    .fee-register-combined-pay:hover { background:#e0e7ff; }
    .fee-register-due-column > strong { display:block; font-size:14px; color:#1e293b; font-variant-numeric:tabular-nums; }
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
