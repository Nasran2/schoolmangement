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
and JavaScript tests. Fee views include `class-fees.partials.scripts`, which reads
the trusted application copy at `base_path('public/js/class-fees.js')` and embeds
it synchronously before the deferred Alpine application. The script is marked
with a source hash. It does not fetch a separately cached public URL; stale
document-root copies, custom `public_path()` values and CDN/browser caches cannot
substitute older factories. Upload the standalone file and Blade partial together.
If the file is missing, the page displays a specific upload message instead of
throwing a server error. Regression tests cover an old custom public copy, missing
application source, script ordering and older compiled globals.

These fee controls require no Vite rebuild. Other bundled application changes
still require `public/build/manifest.json` and every referenced asset to be uploaded
together. Clear compiled views after replacing the Blade partial; without a
terminal, delete only compiled `.php` files inside `storage/framework/views/`,
preserving the directory and `.gitignore`.

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

Payment Category is fixed on the dedicated exam and facility collection pages.
The monthly form includes
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

**Pay [fee] & view bill** opens the existing printable receipt. **Pay [fee]** returns
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

## Bulk make unpaid

Open **Exam & Facility Fees → Bulk payments → Make unpaid**, or use the register's
**Bulk make unpaid** shortcut. This mode requires `revenue.delete`; collection
continues to require `revenue.add`. Choose the year, class/search and exam term or
facility fee, select students, add them to the reversal list, review the receipts
and amounts, then enter a reason and save. Up to 500 student fees can be processed
per batch, including students from several classes.

The paid-mode list includes fully and partially paid fees, historical settlements
and prepaid future fees. Former-class payments are grouped under the class that
owns the receipt, even when an old class move lacks promotion history. Inactive
and alumni students require the existing checkbox. Fees with refunds/waivers or
unsupported cheque states remain visible with a review explanation and cannot be
queued. Pending holds attached to a selected partly paid fee are also cancelled so
they do not continue reserving that balance.

`ClassFeeReversalService` builds a reviewed payment fingerprint and rechecks it
under class, student and receipt locks before cancelling anything. The existing
`ClassFeePaymentService::cancel()` handles each revenue/history record, retaining
bill numbers and audit history. Revenue receipts are cancelled with zero income;
history-only records stop settling the fee without changing income. No refund or
new revenue is created. Future fees revert to Upcoming until their starting date.

The whole batch is transactional: a stale, invalid or adjusted receipt rolls back
all cancellations, audit entries and the batch token. Existing `class_fee_batches`
provides retry protection, with the reversal action included in the payload hash.
An exact retry cannot cancel a new payment received after the successful reversal.
A summary audit records the batch, reason, fee count and cancelled-receipt count;
individual cancellation audits retain original payment data and the reason.

The separate Blade view uses the standalone `bulkClassFeeReversals` factory from
`public/js/class-fees.js`, loaded before Alpine, so shared hosting needs no asset
build. No schema change is required. Regression coverage is in
`ClassFeeBulkUnpaidTest` and `tests/JavaScript/classFees.test.mjs`.

## All-students fee register

Open **Exam & Facility Fees → All students** (`/class-fees?view=students`). The
Overview remains available separately. Search by name or admission number updates
the list and totals after a short typing delay; changes to class, year, status,
term selection, facility inclusion, inactive/alumni or former-class filters update
immediately. Former students are excluded by default. Students without configured
or selected fees remain visible under the All students status, including students
without a class. Other statuses retain the register's existing balance rules.

The page defaults to 25 class entries at a time, with 10/25/50/100 row choices. PDF and CSV downloads contain **all**
matching students and selected fees, regardless of the current page. Download
permission is `reports.download`. CSV uses a separate row per student/fee and
neutralizes spreadsheet formula prefixes in text fields.

Each applicable fee has **Pay** (`revenue.add`), and paid fees have **Make unpaid**
(`revenue.delete`). Both use dialogs and preserve the current filters. Collection
uses the existing payment service, bill numbering, bank validation, historical
payment option and pending-cheque rules. The bill link appears after saving a
revenue payment. Existing receipt **Edit payment** links require `revenue.manage`.
Making a fee unpaid requires a reason and cancels all reviewed active receipts on
that fee through the same atomic reversal service as bulk make unpaid. The popup
shows receipt references and accounting effects; refunds/waivers block reversal
for individual review. Receipt changes after opening the dialog require reloading
and reviewing it again. Audit records and cancelled bills are retained.

Shared filter validation and Blade partials keep the Overview, All students,
exports and action returns consistent. `public/js/fee-register.js` is a standalone
deferred asset, so shared hosting requires no Vite build. Overlapping live-search
responses are cancelled and older results ignored; Apply filters falls back to a
normal GET if live refresh fails. No database migration is required.


## Facility collection and paid-receipt editing

Dedicated exam/facility collectors have a read-only category and always record
revenue. Their collection POST forces `record_revenue=true`; there is no accounting
checkbox in this section. The register and bulk historical-entry workflows retain
their own accounting options. Pending cheques still enter income only after passing.

Facility amounts default to the configured fee for an unpaid cycle, or its remaining
collectable balance after partial payments. The amount is editable, and **Use remaining
facility fee** resets it. Existing balance validation prevents duplicate/overpayments.
Fully settled fees display **Facility Fee fully paid**, the recorded total, and
**Edit / take payment** receipt links for users with `revenue.manage`.

Collection edit links carry `collection=1`. This edit context defaults the payment
date to today (still editable), hides the accounting checkbox, and submits a fixed
revenue entry. Saving opens the printable bill. Existing revenue receipts update in
place without adding a second payment; historical settlements convert to one new
revenue receipt, retaining the cancelled history and audit trail. Partial corrections
reopen the appropriate remaining fee balance. Existing refund/waiver and cheque
correction protections still apply. Normal edits opened from other screens retain
the original date and accounting controls.

## Compact All students layout

The All students view omits summary/due cards; Overview retains them. Its filter
form uses a compact grid with class, year, payment status and instant search,
followed by inline activity/history filters and download actions. Exam terms and
facility inclusion share one horizontal row. Term chips show term/year; full dates
remain in their title and accessible checkbox label, and in the table headers.
On narrow screens or when many earlier-year terms are selected, this row scrolls
horizontally rather than consuming several vertical rows. The existing Overview
filter layout is unchanged.

Navigation links sit beside the page title. Table headings, cells and actions use
reduced spacing; fee status and paid amount share a line when space allows. A
1265 × 713 browser preview with paid receipts shows two complete student rows and
the beginning of a third without scrolling. Instant search fragments use the same
compact results partial. PDF/CSV contents and payment behavior are unchanged.


## Numbered student pagination

All students has pagination above and below the results, with First, Previous,
numbered pages, Next and Last. The current page is highlighted and distant pages
use ellipses. Boundary controls are disabled appropriately. Rows per page supports
10, 25 (default), 50 or 100, validated through the shared register filters.

Page links retain class/year/status/search/term/activity filters and page size.
Changing a filter or page size starts at page one. Out-of-range pages clamp to the
last available page. Live page navigation brings the results into view, and saving
from a payment dialog refreshes the current page (clamped if the result count has
changed). PDF/CSV exports continue to include all filtered rows, independent of
page and page size. Pagination markup is shared by the top/bottom controls.

## Optional computer fees per term

Class Rooms → Manage → Edit has **Computer fee per term (Rs) — optional** beside
exam fee setup. Blank/zero creates no computer charge. A positive amount uses the
same three starting/ending dates as exam terms, with the existing four-month
suggestions. Computer-only classes can also use that calendar. The fee becomes due
at term start; a student joining before/on its ending date owes that term. Finished
terms before admission do not apply. Existing exam/facility receipts are unchanged.
Rate/calendar corrections use the existing fee schedule history protections.

Revenue → **Collect Exam Fee** offers a checked **Include Computer Fee · Term N**
when the student has a collectable computer balance for that same class/term.
The two editable amounts default to their remaining balances and update one live
total. Uncheck it for exam-only payment. **Collect Computer Fee** collects computer
balances independently. The categories stay fixed; these collectors always record
revenue and ledger entries, with cheque income waiting for clearance.

Combined collection runs in one database transaction and reserves one numbered
printed bill. Two accounting records retain their correct categories and balances:
for example BILL-1000 (exam) and BILL-1000-C (computer). Both receipt links open the
same combined bill showing the term, each fee and the total. Passing/returning
either component of a combined cheque updates all active components together;
clearance cannot precede either cheque date. Cancellations remain cancelled.
Corrections and make-unpaid actions apply to the chosen fee component, preserving
the other fee and the audit trail. The printed bill reflects later corrections,
cancellations, refunds and cheque status.

Bulk payments defaults to exam and computer terms. Choosing Term 1/2/3 includes
both fees; separate exam/computer and facility options remain available. Exam and
computer rows for the same student/class/term share a bill when both are included
in revenue. History-only rows stay historical; use the bulk accounting checkbox
for payments received earlier rather than adding that income again. Changed or
invalid rows roll back the whole batch. Submission IDs make retries idempotent.

Computer fees are included in the register, paid/unpaid corrections, selected-term
filters, PDF/CSV downloads and report details. They have a separate due summary
and dashboard card when configured (using the existing exam fee widget permission).
Unchecking computer inclusion keeps its toggle available for re-enabling.
Shared JS assets are standalone; no migration or Vite build is required.

## Grouped student fee register

All students now groups exam and computer cycles sharing the exact starting date,
ending date and term number into one term column. Each component retains its fee
label, status, balance, Pay and Make unpaid actions. Pay both fees appears only
when both matching fees have a collectable balance. Receipt references and Edit
payment links expand under Payment details / edit. This is presentation grouping;
individual balances, receipt IDs and accounting behavior are unchanged.

Facility Fee always follows every selected term and immediately precedes Due now.
When earlier years are included, each dated term gets its own column, annual
facility entries stack in the final fee column, and each student appears once.
Narrow screens/long year ranges scroll horizontally with student names pinned.
The normal three-term layout fits the 1265px desktop preview with all three sample
students visible when the results are in view. Neutral row backgrounds, subtle
status badges, consistent action links and a lightly shaded total replace the
previous seven separate fee columns. Instant search reuses the same view partials.

ClassFeeTableColumns owns display grouping. The shared tracker also sorts types
explicitly as exam, computer, facility, so annual fees cannot interleave with
computer terms based on their due date. Overview/PDF payment counts come before
the fee columns to leave Facility Fee next to Due now. Exports retain individual
fee breakdowns and all filtered students. No asset build or migration is needed.

## Independent bulk fee and term filters

Bulk payments and Make unpaid share Fee type plus three independent term
checkboxes. Choose Computer fee only with Term 1, Term 2, Terms 1 & 2, or any other
subset. Exam-only and combined exam/computer selections use the same term controls.
Annual facility fees ignore term selection; All fees includes facility plus the
chosen term fees. All terms also retains legacy quarterly cycles. Clear terms
explicitly excludes term fees rather than silently selecting them again.

Only candidates with a matching available fee appear. Select all applies to the
current fee, terms, class and search. Adding queues only currently visible selected
students and matching cycles, without duplicate rows. Filters do not modify items
already in the review list; the interface states that explicitly. Amount editing,
accounting inclusion, the 500-fee limit and all server validation remain unchanged.

The standalone script exposes schoolFeeForms.payments / schoolFeeForms.reversals.
The Blade pages use these names so older compiled app bundles that reassign
bulkClassFees cannot override the new form. Both forms share one selector partial
and one JS filter implementation. No Vite build or migration is required.

## Combined payments use the existing school receipt

The combined-receipt view is now an adapter to revenue.receipt and the shared
x-receipt component. Exam + computer payments use the established monochrome
school heading, student/guardian/address lines, bill/date, amount in words, term
paid, grade/admission number and signature. Both accounting component links print
the same main bill, with two fee amounts and one total. The separate accounting
references remain in Revenue; the printed family-facing receipt uses the main bill.
Existing combined payments automatically use this layout without rewriting records.

The adapter computes active totals, refunds/waivers and net collected. Pending
cheques print their pending notice with zero net collected until clearance. Returned
or cancelled components print zero with their status/reason; valid remaining fees
stay visible and the banner says Receipt Updated. Different edited component dates
print beside their fee, while the main bill retains the primary payment date.
Combined totals are rounded to two decimals before generating amount words, and
nonzero cents are spelled out. The original single-payment paths are preserved.

Combined receipts use one receipt-print region and the existing 9.8in by 5.5in print
format. Screen width adapts to the content area; print height can grow instead of
clipping correction/refund information. Standard combined print text is compact.
No asset build or database migration is needed.
