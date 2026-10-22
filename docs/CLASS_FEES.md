# Exam and facility fees

## Three-term calendars and bulk collection (22 October 2026)

New class forms default to **Use three editable terms**. Enter Term 1's start and
end. The browser generates Terms 2 and 3 as four-month periods; every date remains
editable. Calendars repeat annually. Non-overlapping dates are validated on the
server. Fees are due on the start date, remain due during the term, and show
**Overdue** after the end date. A future term can be prepaid.

Existing quarterly schedules remain intact until an administrator enables the
three-term calendar in Class Rooms. During conversion, the first, second and third
legacy installments in each calendar year map to the corresponding new term.
Receipt amounts, bill numbers, payment dates and accounting entries are retained.
An active fourth legacy receipt prevents conversion; correct/cancel that receipt
first. Cancelled fourth receipts remain as audit history. When starting a later
year, the earlier schedule is closed and retained when it has payment history.
Before a class fee has any payment records, its amounts and dates are editable
setup, including past dates and changes outside the original cadence. The current
unpaid schedule is updated in place and setup-only older versions are consolidated
so obsolete charges do not survive a correction. Blank or zero removes an unpaid
setup. Once payment records exist, changing the rate requires a future calendar
after prepaid installments and after the previous term ends. Standard month-end and leap-year calculations do not drift.

Open **Exam & Facility Fees → Bulk payments**:

1. Choose the installment year. Select a class or search names/admission numbers
   across classes. Choose Term 1, 2, 3, all exam terms, facility fees, or both fees.
2. Select individual students or **Select all matching students**, then **Add
   selected to payment list**. Repeat searches to build a list across classes.
   Duplicate student/installment entries are ignored.
3. Review and edit each row's amount, remove unwanted rows, and choose whether the
   row counts as revenue and ledger income. Both are controlled by one checkbox,
   checked by default. The common checkbox changes every queued row and the
   default for subsequently added rows.
4. Set the original payment date and method, then save. Different dates can be
   recorded in separate batches. A batch supports 500 installment rows; its JSON
   form field avoids PHP's normal input-variable count limit.

For Terms 1 and 2 already paid before system entry, select those terms and students,
**uncheck revenue & ledger**, and enter the original payment date. These payments
settle the same student fee balance but do not create revenue, change cash balances,
consume bill numbers, or enter ledger/financial reports. If an original receipt
already exists in Revenue, use **Assign an earlier receipt** instead of creating
another payment. Include inactive/alumni when recording their historical fees.

On the installment table, **Edit / delete** opens the dedicated payment editor.
The standard Revenue edit action also opens this editor for fee receipts. Correct
the amount, payment date, notes, or revenue checkbox. Student and installment
identities stay fixed. Switching accounting inclusion cancels the original record
and creates a replacement in the correct source inside one transaction. Deletion
cancels the entry, restores the balance and removes its income while retaining the
bill and audit trail. Receipts with refunds/waivers must have those adjustments
resolved in Revenue before direct correction, preserving reconciliation.

Fee tracker and PDF totals labelled **Payments recorded** include historical
settlements; financial revenue/ledger totals include only revenue receipts.
Pending cheques reserve balances as before. Bulk collection accepts cash/bank
transfers; the individual form continues to support pending cheques.

### Maintenance and validation

- Migration: `2026_10_22_000001_add_class_fee_terms_and_payment_history.php` adds
  calendar JSON, `class_fee_payment_histories`, and `class_fee_batches`.
- `ClassFeeService`: calendars, eligibility, installments, unified balances.
- `ClassFeePaymentService`: shared single/bulk collection, corrections and audit.
- `ClassFeePaymentController`: bulk page and payment editor, using existing
  `revenue.add`, `revenue.manage`, and `revenue.delete` permissions.
- Historical settlements are stored separately, so existing financial queries
  cannot accidentally count them. `paymentMap()` combines both payment sources
  only when calculating fee settlement status.
- Bulk batches validate every row and commit atomically under class/student locks.
  A reused batch ID returns the original result; changed payloads are rejected.
  An invalid row rolls back the batch, payments, bill numbers and audit entries.
- Run `php artisan test --filter='ClassFeeTest|ClassFeeBulkPaymentTest'` and
  `node --test tests/JavaScript/classFees.test.mjs`. Build assets with
  `npm run build` after changing the browser logic.

Legacy schedules continue to use quarterly dates until explicitly converted. New
class forms use three editable terms by default.

## Existing tracker behavior

The tracker filters by class, calendar year of the installment starting date,
student name/admission number, payment status, or all years. The exam screen
selects all available exam terms and includes facility fees by default. Selection
checkboxes affect screen totals and PDF output together. Changing class or year
resets the installment selection; clearing it preserves an empty selection.

Student joining dates and promotion history determine class eligibility on the
installment starting date. Students who moved classes retain their earlier dues.
Pending cheques reserve balances until passed or returned. Refunds reopen the
refunded portion; waivers settle fees separately from collected income.

Use **Download PDF** to export the current selections. Both single-class and
all-class reports support long histories, repeated headers and page numbering.
Download requires `reports.download` in addition to fee access.

View access accepts `class_fees.view`, `revenue.add`, `revenue.manage`, or
`revenue.categories.manage`. Dashboard cards use
`dashboard.widget.exam_fees.view` and `dashboard.widget.facility_fees.view`.
Admin and Super Admin retain full access. The original fee-schedule migration is
`2026_10_20_000002_create_class_fee_schedules.php`.

## Hosting the browser controls

`public/js/class-fees.js` is the single implementation of the calendar and bulk
selection factories. `resources/js/classFees.js` re-exports it for the Vite build
and JavaScript tests. Both fee views include `class-fees.partials.scripts`, which
loads the standalone file synchronously before the deferred Alpine application.
Its modification time versions the URL. Upload the standalone file and the Blade
partial together. This keeps the controls working even when an older application
bundle is still deployed. A regression test covers the standalone runtime.

When deploying, include `public/build/manifest.json` **and** every referenced file
under `public/build/assets/`. Updating PHP/Blade files alone does not update Vite
JavaScript. An outdated bundle can produce `termCalendar is not defined`, followed
by `feeAmount is not defined`, and leave the date controls hidden. Clear compiled
views with `php artisan view:clear` after replacing the view files.

## Correcting setup before the first payment

The no-payment check applies separately to each class and fee type. An exam
payment does not lock an unpaid facility fee. Both Revenue receipts and historical
settlements are checked across all versions of that fee; pending and cancelled
records also retain the history protections. Term ordering and overlap validation
continue to apply. Corrections run under the same class lock as collection and
commit atomically with the classroom update.
