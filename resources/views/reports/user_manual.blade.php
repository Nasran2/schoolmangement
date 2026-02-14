<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Manual</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 16px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #ddd; }
        h3 { font-size: 12px; margin: 12px 0 6px; }
        .meta { font-size: 11px; color: #444; margin-bottom: 12px; }
        .small { font-size: 11px; }
        .muted { color: #666; }
        ul, ol { margin: 0; padding-left: 18px; }
        li { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e5e5; padding: 6px; vertical-align: top; }
        th { background: #f7f7f7; font-size: 11px; text-align: left; }
        code { font-family: DejaVu Sans, monospace; font-size: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <h1>User Manual</h1>
    <div class="meta">
        @if(!empty($school))
            School: <strong>{{ $school }}</strong><br>
        @endif
        @if(!empty($company))
            Developed by: <strong>{{ $company }}</strong><br>
        @endif
        Generated at: <strong>{{ $generated_at }}</strong>
    </div>

    <h2>1) Overview</h2>
    <div class="small">
        This system is a web-based School Finance & Administration platform that helps you manage students and teachers,
        record revenue and expenses, and generate detailed operational and statutory reports (including EPF/ETF).
    </div>

    <h2>2) Login & Navigation</h2>
    <ol class="small">
        <li>Open the system URL in your browser.</li>
        <li>Login using your username/email and password.</li>
        <li>Use the menu to open modules like Students, Teachers, Salary Payments, Reports, and Settings.</li>
    </ol>
    <div class="small muted">
        Note: What you can see depends on your role permissions.
    </div>

    <h2>3) Roles & Permissions (RBAC)</h2>
    <div class="small">Use RBAC to control access per user role (recommended: least privilege).</div>
    <h3>3.1 Create roles</h3>
    <ol class="small">
        <li>Go to <strong>RBAC → Roles</strong>.</li>
        <li>Create roles like <em>Admin</em>, <em>Accountant</em>, <em>Office Staff</em>, <em>Principal</em>.</li>
        <li>Assign permissions for each module (students, teachers, salary, reports, settings).</li>
    </ol>

    <h2>4) Students — Add & Manage</h2>
    <h3>4.1 Add a student</h3>
    <ol class="small">
        <li>Open <strong>Students</strong>.</li>
        <li>Click <strong>Add</strong>.</li>
        <li>Fill student details (name, class/grade, guardian contact, etc.).</li>
        <li>Save.</li>
    </ol>

    <h3>4.2 Update / search / statement</h3>
    <ul class="small">
        <li>Use search to find a student quickly.</li>
        <li>Open student profile to edit details.</li>
        <li>Use <strong>Statement</strong> to view payment/due history.</li>
    </ul>

    <h3>4.3 Promotions / alumni</h3>
    <ul class="small">
        <li>Promote/demote a student (or do bulk promote/demote if enabled).</li>
        <li>Mark students as Alumni when they leave, manage leaving documents, and readmit if needed.</li>
    </ul>

    <h3>4.4 Bulk upload</h3>
    <ol class="small">
        <li>Open Students → Bulk Upload.</li>
        <li>Download template and fill carefully.</li>
        <li>Upload and fix any validation errors shown by the system.</li>
    </ol>

    <h2>5) Teachers — Add & Manage</h2>
    <h3>5.1 Add a teacher</h3>
    <ol class="small">
        <li>Open <strong>Teachers</strong>.</li>
        <li>Click <strong>Add</strong>.</li>
        <li>Enter teacher details and save.</li>
    </ol>

    <h3>5.2 Manage salary setup</h3>
    <ul class="small">
        <li>Open a teacher profile to update salary-related details (components/deductions if configured).</li>
        <li>Confirm EPF/ETF related settings as per your payroll rules.</li>
    </ul>

    <h3>5.3 Visiting teachers + bulk upload</h3>
    <ul class="small">
        <li>Use Visiting Teachers module if your school uses visiting staff.</li>
        <li>Use Teacher Bulk Upload for fast onboarding.</li>
    </ul>

    <h2>6) Teacher Salary Payments + EPF/ETF</h2>
    <h3>6.1 Configure salary components (one-time)</h3>
    <ol class="small">
        <li>Go to Settings → Salary Components.</li>
        <li>Add/update components (basic, allowances, deductions, EPF/ETF items if used).</li>
    </ol>

    <h3>6.2 Create a salary payment</h3>
    <ol class="small">
        <li>Open Teacher Salary Payments.</li>
        <li>Click Create.</li>
        <li>Select teacher and month, enter the salary details.</li>
        <li>Save.</li>
        <li>Generate payslip/receipt and email payslip (if email is configured).</li>
    </ol>

    <h3>6.3 EPF/ETF reports</h3>
    <ul class="small">
        <li>Reports → Teacher EPF</li>
        <li>Reports → Teacher ETF</li>
        <li>Reports → Company EPF</li>
        <li>Reports → EPF/ETF Totals</li>
    </ul>

    <h2>7) Revenue / Fees (Collections)</h2>
    <h3>7.1 Setup categories</h3>
    <ol class="small">
        <li>Open Revenue Categories.</li>
        <li>Create fee categories (tuition, admission, term fees, etc.).</li>
        <li>If needed, link categories to classes for class-based collections.</li>
    </ol>

    <h3>7.2 Collect a payment + print receipt</h3>
    <ol class="small">
        <li>Open Revenue Items → Create.</li>
        <li>Select student, fee category, amount, and payment method (cash/bank/cheque).</li>
        <li>Save and open Receipt to print/download.</li>
    </ol>

    <h3>7.3 Refunds / waivers / cheques</h3>
    <ul class="small">
        <li>Use Refund or Waiver for corrections/discounts with proper records.</li>
        <li>Track cheques and update status (passed/returned) when cleared.</li>
    </ul>

    <h2>8) Expenses</h2>
    <ol class="small">
        <li>Create expense categories first.</li>
        <li>Add expense items with amount, date, and description.</li>
    </ol>

    <h2>9) Reports</h2>
    <div class="small">Common reports available:</div>
    <ul class="small">
        <li>Revenue / Expense / Outflows / Financial summary</li>
        <li>Daily ledger</li>
        <li>Cash transactions / Bank transactions</li>
        <li>Cheque history</li>
        <li>Student due reports + due aging + top due</li>
        <li>Fee collection summary / by class / by category / vs expected</li>
        <li>Seminar and extra-class collection</li>
        <li>EPF/ETF statutory reports</li>
    </ul>

    <h2>10) Settings & Backups</h2>
    <ul class="small">
        <li>Configure Email and SMS and run test messages.</li>
        <li>Configure Printer settings for receipts and payslips.</li>
        <li>Set Opening Balance for accurate reporting.</li>
        <li>Use Backups settings to run and download backups regularly.</li>
    </ul>

    <h2>11) Troubleshooting</h2>
    <ul class="small">
        <li><strong>Missing menus:</strong> ask admin to update role permissions.</li>
        <li><strong>Email/SMS not sending:</strong> ensure real provider credentials are configured.</li>
        <li><strong>Printing issues:</strong> confirm printer settings and browser print permissions.</li>
    </ul>

    <div class="small muted" style="margin-top: 14px;">
        Technical info: {{ $app['name'] }} (Laravel {{ $app['laravel_version'] }}, PHP {{ $app['php_version'] }}).
    </div>
</body>
</html>
