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
student name/admission number, payment status, or all years. Class lists and PDFs
show current class members by default. **Include former class students** restores
earlier memberships for viewing or collecting their outstanding installments;
this changes display totals, without deleting any balances or payments. The exam screen
selects all available exam terms and includes facility fees by default. Selection
checkboxes affect screen totals and PDF output together. Changing class or year
resets the installment selection; clearing it preserves an empty selection.

For three-term calendars, students joining anytime through a term's ending date
owe that term and subsequent terms, including admissions on the last day. Terms
that ended before admission do not apply. Class eligibility is assessed on the
term's starting date, or on the joining date for admissions during the term.
Existing students transferring classes retain the class responsible at the term's
start, avoiding duplicate charges within the same term calendar. Legacy
installments without explicit term dates keep their original due-date rule.

Annual facility fees apply from the joining **calendar year**,
regardless of the joining month: an October 2026 admission owes the 2026 facility
fee even if its due date was in January, but owes no 2025 facility fee. If the
joining date is missing, fee start date and then creation date supply the year.
The class at the start of the calendar year (or joining date for new admissions)
owns the annual charge, so a transfer does not create a second facility fee for
the same year. Existing facility payment records preserve their original class
through payment corrections, accounting changes and cancellations. Both payment
sources are batch-loaded for fee grids without affecting monthly-fee revenues.
Students who moved classes retain their earlier dues.
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

## Collecting fees from Revenue

Open **Revenue → Collect Exam Fee** or **Collect Facility Fee**. Both use the
existing Revenue create route with `quick=exam` or `quick=facility` and require
`revenue.add`. The Payment Details form uses the same payment-card design as the
monthly collection screen, with a Billing Summary and fee-specific dues on the
right. Enter a name, admission number or phone number for live suggestions;
choose a current class to restrict the search. Suggestions support arrow keys,
Enter and Escape. Outdated responses cannot replace a newer search. The normal
Search students action remains available if suggestions cannot load.

Earlier years are included by default. Period and inactive/alumni filters are in
**Installment period & student filters**. Previous-class charges belonging to the
selected student remain available here. The earliest collectable installment is
selected automatically; choose a different installment from the selector or
summary. Partial and full payments share existing server balance validation and
idempotent submission IDs. The summary amount updates while entering an amount.

Payment Category supports switching between exam, facility and ordinary revenue
categories while preserving the selected student. The monthly form also includes
exam/facility categories; choosing one navigates to its installment collector
rather than recording an unassigned revenue. An existing ordinary receipt cannot
be converted into an installment merely by changing its category.

Cash and bank receipts are confirmed on saving. Cheques require their date,
number and bank; the optional name on cheque is retained. Pending cheques reserve
the installment and do not settle its balance or enter collected income. Passing
requires a clearance date on/after the cheque date and no later than today;
`confirmed_at` retains the actual clearance date for the daily ledger. Returning
an unpassed cheque releases the reservation. A pending cheque cannot be entered
as historical settlement or have its revenue tracking removed; an already passed
cheque may be converted to historical settlement through the correction service.

**Save & view bill** opens the existing printable receipt. **Save payment** returns
to collection. Revenue bill numbering follows the automatic/manual setting;
manual numbers must be unique, and retries retain the original receipt. Historical
payments settle balances without new revenue bills. Cashiers with `revenue.add`
can print only their own receipts; `revenue.manage` retains access to all receipts.
The receipt route accepts both permissions and checks ownership in its controller.
Print templates use exam/facility labels instead of interpreting their recurrence
interval as a monthly fee. Configured terms and facility years appear on bills,
and the pending-cheque warning is included in the printable receipt itself.

`ClassFeeCollectionController` handles search and builds panels from
`ClassFeeService`; writes remain in `ClassFeePaymentService`. The collector loads
`public/js/fee-collection.js` and `public/css/fee-collection.css` using the guarded,
versioned `class-fees.partials.collection-assets` include. Upload these with the
Blade templates. No Vite build is needed for the collector assets. Run PHP fee
regressions and `node --test tests/JavaScript/feeCollection.test.mjs` after changes.

## Term names in revenue details

Exam payment descriptions use **Exam Fee — Term 1 (2026)** rather than a generic
installment due date. `ClassFeeService::examTermLabel()` resolves configured term
numbers from their saved calendar, including terms across calendar years. Earlier
quarterly calendars use the chronological charge number within the due year;
a genuine fourth legacy charge is still Term 4 until that calendar is converted.

New exam receipts store this label with any staff notes. Existing linked exam
receipts use `Revenue::display_notes` to replace old generated text for display,
including notes retaining a pre-conversion due date. The stored notes, amounts,
payment dates, bill numbers and accounting rows are preserved. Ordinary revenue
and unassigned receipts retain their notes; a term is never guessed from the
payment date. Revenue reports, PDF/export notes, daily ledgers, financial detail,
cash/bank transactions and cheque history use the same formatter. Relevant
queries eager-load the schedules to avoid one extra query per payment.

When multiple terms are paid through bulk collection, every term retains its own
payment and bill and each row names the term it settles. This keeps the details
and totals aligned. Printed exam bills use **Term paid** and the term name rather
than **Installment due**. The Revenue manage list also identifies the paid term.

## Due balance cards

The fee register shows **Due in Term 1**, **Due in Term 2**, **Due in Term 3** and
**Due in Facility Fees** beneath its existing totals. These cards summarize the
same filtered installment balances as the register, for one class or all classes.
They respect year/all-years, selected exam dates, facility inclusion, student
search, payment status, inactive/alumni and former-class filters. Unselected fees
show zero with a selection hint; future fees do not count until their starting date.

`ClassFeeService::dueBreakdown()` reads the filtered tracker sections rather than
recalculating eligibility or settlement. Partial payments, historical settlements,
waivers and pending cheques therefore use the register's existing rules. All-years
mode groups each term number across the included years. Configured calendars keep
their term numbers even across calendar years. Legacy numbering uses the full
calendar before date selection; genuine fourth legacy charges receive an additional
Term 4 card so no balances disappear from the breakdown.

The card grid uses scoped CSS inside the Blade view to support existing hosted
asset bundles without requiring a new build. Regression coverage is in
`tests/Feature/ClassFeeDueCardsTest.php`.
