# Teacher evaluations and HR access

Open **Evaluations > All evaluations** in the sidebar. **Teachers > Teacher Details** opens the read-only directory; each profile includes evaluation history, buttons to add annual/event reports and PDF/Excel templates prefilled with that teacher’s name and class. **Students > Student Details** opens the read-only student directory. The module follows the supplied BIC workbook's 13 criteria, expected standards, weights, rating scale, evidence fields, development fields and performance bands. The source criteria total 100%.

## Access

The migration creates the **HR admin** role. In **Settings > Users**, an administrator can create or edit an account and assign this role. No account or password is created by the evaluation migration. Assign only this role when the account must remain restricted: permissions from additional roles are additive.

HR admin has these permissions:

- `hr.directory.view`: read-only teacher and student directories under `/hr`. These queries explicitly select contact and school details. They never select salary, salary components, fees, dues or transactions.
- `teacher_evaluations.view`: list and read evaluations and instructions.
- `teacher_evaluations.manage`: create, edit, save drafts and complete evaluations.
- `teacher_evaluations.export`: download blank forms, individual reports and filtered reports as PDF or XLSX.

HR admin receives no dashboard, financial, payment, student/teacher management, reporting, settings, user-management or audit-log permissions. Direct requests to those protected routes are denied, including writes. The existing teacher and student pages remain protected by their existing permissions. Both desktop and mobile navigation expose the HR pages.

Admin and Super Admin retain full access. `teacher_evaluations.delete` is separate and is not granted to HR admin. Deletion records a full audit snapshot. A teacher with evaluations cannot be deleted; deactivate the teacher to preserve their history.

## Annual appraisals

Create one appraisal per teacher per appraisal year. The database also enforces this constraint. Teacher and report type cannot be changed after creation. The teacher's name, criteria and weights are saved with the record so subsequent teacher-name or template changes do not rewrite previous reports.

Enter profile details, appraisal period, evaluator, review date, 13 whole-number ratings from 1 to 5 and criterion remarks. Annual forms also allow optional evidence and development entries. A weighted score is `weight x rating / 5`. Use one, two or three supervisor columns. Each supervisor receives a score out of 100 after all 13 ratings are present. The overall result averages only completed supervisors; blank and partial columns are excluded, never counted as zero. Supervisor names are optional. Existing single-review appraisals remain Supervisor 1 with their original score. The browser preview and server use the same weights. Submitted scores, weights and audit-user fields are ignored.

Only the teacher and appraisal year (or event name for an event review) are required to identify a record. Review metadata, ratings, evidence and development details may remain incomplete for either Draft or Completed status. Saving presents an in-page warning listing missing details by name, with Return to form and Save anyway choices. Unused supervisor columns and wholly blank optional rows do not generate field warnings. The server also requires acknowledgement of incomplete details before writing, preserving submitted inputs and the selected status if it needs to return the form. An end date cannot precede an entered start date. Invalid ratings and date formats, duplicate appraisal years and stale versions still block saving. A complete set of ratings in a draft can show a calculated score, but the report remains visibly marked Draft.

## Event evidence, feedback and development

Create a separate report for each teacher and event or function, such as Open Day. Multiple reports for the same teacher are supported. Enter the function name, event date, responsibility, section, evaluator and review date. Evidence and development entries can be added or removed, up to 50 per section.

An event review without evidence warns that no entries were added. Used evidence rows warn about missing performance areas, examples or reviewed-by names; development rows warn about missing goals, actions, success measures or target dates. Save anyway retains partially entered rows. Entirely blank rows are discarded. Event reports do not calculate an annual score.

Both types record teacher comments, management comments, physical signature names and dates, and follow-up notes. An incomplete signature name/date pair or follow-up completion without notes raises a save warning and can be saved after acknowledgement. These are transcriptions, not digital signatures. Scores and comments do not update payroll or promotions.

## Follow-up and editing

The earliest evidence review date or development target date is the next follow-up date. The Overdue filter finds dates before today until a follow-up completion date is entered. Missing review notes produce a save warning. That date closes follow-up for the entire report, so leave it blank while any action still needs review.

Created-by, last-edited-by, timestamps, completion time and a version number are stored. Updates use a database lock and version comparison to prevent stale forms from overwriting another person's changes. Validation errors preserve entered ratings and repeatable rows. A stale-version error requires reloading the current record before saving again. Creates, edits and deletes have full audit snapshots.

## Filters and downloads

Filter by teacher, report type, section, status, teacher/staff-ID/evaluator/event search, annual appraisal year, event-date range, review-date range and overdue/completed follow-up. Dates include both endpoints. An upper date bound can be used without a lower bound. Annual year applies only to annual reports. Event-date filters apply only to event reports. Applying both year and event-date filters produces no matches; clear the irrelevant fields when switching types.

The list shows 20 records per page. Filtered exports use the current form values, including changes not yet applied to the on-screen list, and include every matching full report up to 100 records. Above 100, narrow the filters. A PDF states the supplied filters. Excel includes a summary when exporting multiple or zero records, individual form sheets and English instructions. Zero matches returns an explanatory document.

The entry screens and exports follow the source workbook’s purple headers, lavender section bars and alternating rows. Annual ratings, evidence and development plans use worksheet tables. On screen, scroll sideways for additional columns; the entry number and performance area remain visible. Blank PDF and Excel forms include four evidence/development rows, handwriting spaces, signature spaces and English instructions. Long narrative cells continue onto labelled rows so all text remains printable. Individual reports reproduce the recorded details. PDF uses landscape A4 with page numbers. Excel uses landscape A4, repeating headers, editable rating cells with 1-5 validation and live weighted-score, total and performance-band formulas. Each supervisor total stays blank until all 13 ratings are present. The overall formula averages completed supervisor totals; partial columns do not affect it. Free text is written explicitly as text to prevent spreadsheet formula injection. Long narrative text is split over rows to avoid Excel's row-height limit. Downloads are authenticated, permission-checked and marked private/no-store.

## Optional sample data

Run `php artisan db:seed --class=TeacherEvaluationSampleSeeder --force` to add eight clearly labelled examples: four annual appraisals across the current and previous years and four event reviews. They include completed reports, partial drafts, future and overdue follow-ups, and closed follow-ups. Search for `[SAMPLE]` in Teacher Evaluations to find them.

Three dedicated sample teacher profiles are created as inactive, with zero salary and EPF/ETF disabled. Existing teacher profiles and financial records are not modified. No login accounts or physical signatures are created. The seeder is separate from the default database seeder. Running it again in the same year does not duplicate or overwrite the sample records, including changes made while trying the UI.

## Deployment and verification

Run `php artisan migrate --force`. The migrations add the evaluation table, optional supervisor names, permissions and role assignments. Fresh installations also receive the role through `RbacSeeder`; do not rerun that general seeder just to install this module because its existing behavior resets default login accounts.

Verification covers permissions, nonfinancial directory projections, scoring and bands, drafts and completion, duplicate annual years, concurrent edits, repeatable events and rows, signatures and follow-up, filters and range boundaries, safe XLSX text, template formulas, real PDF/XLSX generation and administrator access. Browser checks cover live scores, repeated entries and retained data after validation. PDF and worksheet previews are visually reviewed with synthetic test data.
