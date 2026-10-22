# Reports overview

The Reports home page starts with a daily overview. Today is selected by default. Presets include yesterday, week to date, month to date, last month and a custom inclusive date range.

- **Total collection:** confirmed student-linked revenue receipts for the selected payment dates, less their refunds.
- **Total revenue:** all confirmed income for those dates, including student collections, less refunds. Collection and revenue must not be added together.
- **Total expense:** operating expenses, salary payments and seminar/extra class teacher payouts for the selected dates. Payouts linked to an expense are counted once. Owner bank transfers are excluded.
- **Pending teacher payments:** remaining salary for the current payroll month, subtracting salary already settled using `base_salary` and `payment_month`. Inactive teachers and teachers whose employment/payment start date is in the future are excluded.
- **Pending student receivables:** current monthly fee ledger balances for active, non-alumni students, using the same computed balance as the student statement. Exam and facility dues are separate.
- **Pending exam/facility fees:** remaining eligible installments due today or earlier across all schedules, including earlier years and class promotion history. Refunds and waivers use the existing class fee tracker rules. Future installments are excluded.

Pending cards always show current balances, regardless of the transaction date filter; the page labels this explicitly. They are not historical balance snapshots. Teacher pending amounts cover the current payroll month, not accumulated historical payroll liabilities.

Each card opens `/reports/overview/{metric}` with the selected dates. Details use the same calculation as the card, display the total and complete paginated list, and offer a text search with its own matching subtotal. Read-only requests do not generate fee cycles, allocations, expenses or payments.

Outstanding detail pages also support grade/class (student and class fee balances), installment year (exam/facility), outstanding payment status (unpaid, partial, pending cheque where relevant), minimum remaining balance and sorting. The prominent matching total includes all filtered records across every page; the pre-filter total remains available for comparison. Clear filters restores the defaults and pagination retains applied filters.

Exam sessions use checkboxes, all selected by default. Session numbers follow all scheduled due dates within each class and calendar year, including settled and future installments, so paying Session 1 or changing the fee amount cannot renumber Session 2. A fourth session is offered for quarterly schedules that contain it. Filtering by grade uses the grade associated with the installment, preserving a promoted student's earlier liabilities.

Teacher details can select a payroll month up to the current month, a teacher, and unpaid/partially settled status. Settlements use the selected `payment_month` through today. Expected salary uses each currently active teacher's current salary setting; historical month selection is not a historical salary-rate snapshot. Teachers whose joining/payment start date falls after the selected month are excluded. The Reports home card continues to show the current payroll month.

The overview requires `reports.view`. Each card and its details additionally require the corresponding revenue, expense, student due, teacher salary or class fee permission. Admin retains full access; the HR role receives no financial access.
