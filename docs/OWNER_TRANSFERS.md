# Owner bank transfers and cash returns

Open **Owner Bank Transfer**. Enter the destination bank/account name or identifying
last digits so the destination appears in the transfer list and daily ledger.
Existing records default to “Owner bank account” until their destination is edited.
The owner account remains external: transfers reduce school cash, and returns
increase school cash. Neither event changes school bank balances, revenue, expenses
or profit.

Each active row offers **Edit**, **Return amount**, and **Delete**. Returns accept an
amount, a date (today by default), and optional notes. Multiple returns are allowed
up to the original transferred amount. The list displays gross transferred,
returned and net transferred amounts, with violet and green backgrounds. Returns
are dated separate records, with a reference and the staff member who entered them.
A fully returned transfer has no remaining amount to return.

Deletion requires a reason. The transfer remains visible in red with its original
amount, reason, timestamp and staff member. Related returns also remain as deleted
history. The transfer and its returns contribute zero to accounting totals. This is
a correction of a mistaken record; use Return amount when cash physically comes
back. Financial edits or deletion of movements included in a saved opening cash
snapshot are rejected to preserve that reconciliation; metadata can still be
corrected, and returns after the snapshot can still be recorded.

The original transfer amount cannot be reduced below total recorded returns. Its
date cannot move after the first return date. Transfer and return dates cannot be
in the future. Returns cannot predate their transfer. Amounts must be positive with
at most two decimal places. Every mutation requires `owner_transfers.manage`.

`OwnerTransferService` serializes changes with the existing transfer cache lock and
uses database transactions and row locks. Return submission UUIDs prevent duplicate
cash additions. A revision number rejects edits/returns from stale pages. Edits and
new transfers must leave cash nonnegative at all intervening transaction dates,
not only on the transfer date and today. Audit logs preserve creation, before/after
edits, deletion reasons and return details.

`OwnerTransferReportService` builds dated gross, returned and net totals. The daily
ledger and PDF show separate violet transfers and green returns, the destination,
references and net amount. A return in a later reporting period is included even
if the original transfer is outside that period. Cash reports record it as cash in;
financial summaries subtract it from net owner transfers, and outflow reports use
a negative transfer-return adjustment. It never becomes operating income. Daily
ledger Excel exports put each return in its own row with its amount in the cash-in
column. Bank-only reports exclude owner transfers and returns.

## Deployment

This change requires `2026_10_23_000001_add_owner_transfer_corrections_and_returns`.
With a terminal, run `php artisan migrate --force`. For shared hosting without a
terminal, import the provided `owner-transfer-schema.sql` once through phpMyAdmin,
then upload the application files and clear compiled views and route cache in
File Manager. The migration detects schema already installed through phpMyAdmin,
so it remains safe to run later. Existing transfers/payments are preserved.

Regression tests: `OwnerTransferCorrectionsTest` and `OwnerTransferTest`. Browser
verification uses an isolated SQLite preview database with sample records, never
the live database.
