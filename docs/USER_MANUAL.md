# User Manual

**System:** School Finance & Administration System  
**School:** Polgahawela British International College  
**Developed by:** twinsofte.com  
**Last updated:** 2026-02-14  

---

## 1) What this system does (overview)
This system helps the school manage:
- Students (add/edit, statements, promotions/demotions, alumni, bulk upload)
- Teachers (add/edit, visiting teachers, bulk upload)
- Teacher salary payments and EPF/ETF reports
- Revenue / fee collections (receipts, refunds/waivers, cheque handling)
- Expenses (categories and expense items)
- Reports (finance, ledger, cash/bank, dues, EPF/ETF, collections)
- Security with Roles & Permissions (RBAC)
- Settings (general, printer, SMS/email, opening balance, backups)
- Audit logs (track key actions)

---

## 2) Login & basic navigation
1. Open the system URL in a browser.
2. Login with your username/email and password.
3. After login, use the left-side menu (or top navigation) to access modules.

Common pages (may vary by your permissions):
- Dashboard: `/dashboard`
- Students: `/students`
- Teachers: `/teachers`
- Teacher Salary Payments: `/teacher-salary-payments`
- Reports: `/reports`
- Settings: `/settings/*`

---

## 3) Roles & Permissions (RBAC) — IMPORTANT
Roles & permissions control what each user can see/do.

### 3.1 Create / manage roles
1. Go to **RBAC → Roles**.
2. Create a role (example: `Accountant`, `Admin`, `Data Entry`, `Principal`).
3. Assign permissions to the role (view/add/edit/delete, reports access, settings access).

### 3.2 Good recommended roles (example)
- **Admin**: full access (settings + RBAC + backups)
- **Accountant**: revenue, expenses, salary payments, reports
- **Office Staff**: students/teachers add/edit, limited reports
- **Principal**: view dashboards and reports only

Tip: Give the minimum permissions needed for the job.

---

## 4) Academic Year (basic setup)
The system uses an academic year selection.
1. Login.
2. Set academic year when prompted (or from a settings/selector).

---

## 5) Students — add & manage
### 5.1 Add a new student
1. Go to **Students → Add** (`/students/create`).
2. Fill student details (name, grade/class, guardian contact, etc.).
3. Save.

### 5.2 Edit / view student
1. Go to **Students** (`/students`).
2. Search and open the student profile.
3. Use **Edit** to update details.

### 5.3 Student statement
1. Open a student profile.
2. Choose **Statement** to view payments/due history.

### 5.4 Promotions / demotions
- Single student promotion/demotion is available from the student page.
- Bulk promote/demote is available from the promotion module.

### 5.5 Alumni & leaving documents
1. Open a student profile.
2. Use **Mark Alumni** when a student leaves.
3. Upload/update leaving documents if used by your school workflow.
4. You can **Readmit** an alumni student if they return.

### 5.6 Bulk upload students
1. Go to **Students → Bulk Upload**.
2. Download the template.
3. Fill the sheet carefully and upload.
4. Review validation errors (if any) and re-upload.

---

## 6) Teachers — add & manage
### 6.1 Add a teacher
1. Go to **Teachers → Add** (`/teachers/create`).
2. Enter teacher details.
3. Save.

### 6.2 Manage teacher salary setup
1. Go to teacher profile.
2. Use **Salary** section to configure salary components (if enabled).
3. Confirm EPF/ETF related fields (if applicable in your configuration).

### 6.3 Visiting teachers
1. Go to **Visiting Teachers** (`/visiting-teachers`).
2. Add/edit as needed.

### 6.4 Bulk upload teachers
1. Go to **Teachers → Bulk Upload**.
2. Download template, fill, and upload.

---

## 7) Teacher Salary Payments + EPF/ETF
### 7.1 Configure salary components (one-time)
1. Go to **Settings → Salary Components**.
2. Add / update salary components used in your school (basic, allowance, deductions, etc.).

### 7.2 Make a salary payment
1. Go to **Teacher Salary Payments** (`/teacher-salary-payments`).
2. Click **Create**.
3. Select teacher, month, and enter required salary values.
4. Save.

After saving, you can:
- View the payment record
- Generate **Payslip**
- Generate **Receipt**
- Email payslip to teacher (if email is configured)

### 7.3 EPF/ETF reports
Go to **Reports** and open:
- Teacher EPF report
- Teacher ETF report
- Company EPF report
- EPF/ETF totals

Use these to prepare payroll statutory submissions.

---

## 8) Revenue / Fees — collection, receipts, refunds
### 8.1 Revenue categories
1. Go to **Revenue Categories**.
2. Create categories for fees (tuition, admission, term fee, etc.).
3. If the workflow uses class-based collections, configure category/class relationships.

### 8.2 Collect a payment (revenue item)
1. Go to **Revenue Items**.
2. Click **Create**.
3. Search/select student.
4. Select category, amount, payment method (cash/bank/cheque).
5. Save.

### 8.3 Print / download receipt
1. Open the revenue item.
2. Click **Receipt**.
3. Print or download.

### 8.4 Refunds / waivers
1. Open the revenue item.
2. Choose **Refund** or **Waiver**.
3. Confirm amount and reason.

### 8.5 Cheques
- Record cheque payments during collection.
- Track cheque status and mark as passed/returned.

---

## 9) Expenses — record and manage
### 9.1 Expense categories
1. Go to **Expense Categories**.
2. Add categories (utilities, salaries, maintenance, etc.).

### 9.2 Add an expense item
1. Go to **Expense Items**.
2. Click **Create**.
3. Enter category, date, amount, description.
4. Save.

---

## 10) Reports — what’s available
From **Reports** (`/reports`), common reports include:
- Revenue, Expense, Outflows, Financial summary
- Daily ledger
- Cash transactions / Bank transactions
- Cheque history
- Student due reports, due aging, top due
- Fee collection reports: summary, by class, by category, vs expected
- EPF/ETF reports (teacher + company) and totals
- Seminar and extra-class collection reports

Tip: Use report filters (date range, class, category) to narrow results.

---

## 11) Settings & system tools
### 11.1 Email & SMS settings
- Configure Email provider in Settings and run test email.
- Configure SMS provider and run test SMS.

### 11.2 Printer settings
- Configure receipt/payslip printer settings.

### 11.3 Opening balance
- Set initial opening balance for accurate financial reporting.
- Use reset only with admin approval.

### 11.4 Backups
- Use Settings → Backups to configure and run backups.
- Download backup files and store them safely.

---

## 12) Daily operations checklist (recommended)
- Confirm correct academic year selected.
- Record daily revenue collections and print receipts.
- Record daily expenses.
- Monitor cheques.
- End of month: generate salary payments and EPF/ETF reports.
- Weekly/monthly: download backups.

---

## 13) Troubleshooting (quick)
- **Can’t see a menu/module:** your user role may not have permission.
- **Receipt/payslip not opening:** check printer settings and browser pop-up settings.
- **Email/SMS not sending:** ensure providers are configured (not in “log mode”).

---

If you want, I can tailor this manual to match your exact menu names and workflow (cash/bank/cheque process, monthly fee allocation rules, and who approves refunds).