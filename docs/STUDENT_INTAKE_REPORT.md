# Student Intake report

Open **Reports → Student Intake**, or the Student Intake card in **All Reports**.
The route is `reports.student_intake` (`GET /reports/students/intake`).

The report uses the student's saved `joining_date`, including both selected boundary
dates. The default period runs from the first day of this month through today. It
does not infer admission dates from `created_at` or include records without a
joining date. The class shown and filtered is the student's **current recorded
class**, not a reconstructed historical class at admission.

## Filters and results

- Date range, current class (including unassigned), status and literal name or
  registration-number search.
- All statuses are included by default, so inactive and alumni students remain in
  historical intake totals. Active/inactive filters exclude alumni; the alumni
  filter includes alumni regardless of their active flag.
- This month, last month and this year shortcuts preserve the other filters.
- Counts show total matching students, represented classes and distinct joining
  dates. Class chips show filtered counts and narrow the list when clicked.
- Registration number is the first column, followed by name, joining date, current
  class and status. Sort by newest/oldest joining date or student name. Pagination
  supports 10, 25, 50 and 100 rows while preserving filters.
- Click a registration number or name to open the student profile. Users with
  `students.manage` open the full profile; those with only `hr.directory.view`
  open the existing directory profile. Report-only viewers see plain text.
- Totals and PDF/CSV exports cover **all matching students**, independently of the
  screen page. CSV uses UTF-8 with a BOM and protects user-entered cells against
  spreadsheet formula execution. PDF uses the configured school name.

## Access and implementation

The route inherits `reports.view`. Both exports also require `reports.download`.
No new permissions, database migration, SQL import or JavaScript build is needed.
The controller validates dates, filter values and pagination before querying.
The exclusive next-day upper boundary includes the whole final day for either
DATE or datetime storage. Shared query filters drive the list, summaries and
exports. Report styles are scoped with `intake-*` names and included in the view
for File Manager deployments that have older compiled frontend assets.

`StudentIntakeReportTest` covers date boundaries, defaults, nullable values,
class/status/search filters, totals, sorting, pagination, full exports, PDF
rendering, invalid inputs, permissions, CSV escaping and empty results. Browser
review uses a separate temporary database; school records are unchanged.

For cPanel, use `output/reports/student-intake-report-update.zip` and its included
instructions. It contains the preceding fee/receipt fixes as well as this report.
