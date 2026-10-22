# Users, roles, and dashboard access

Open **Settings → Roles & Permissions** to create a custom role and select its permissions. Then use **Settings → Users** to create or edit staff accounts and assign one or more roles. Permissions from multiple roles are combined.

## Account management

- Search and filter users by name, username, email, role, or account status.
- The Users and Active cards show totals across all pages, independently of list filters.
- Edit account details and roles. Leave both password fields blank to retain the current password, or enter a matching password of at least eight characters.
- Deactivation prevents new sign-ins and ends access on the next request for existing sessions.
- A user cannot remove their own roles, deactivate themselves, or delete themselves through user management.
- The last active Super Admin is protected, including from profile deletion. Installations with only an Admin account also protect their last administrator.
- Managers can only assign roles and manage accounts within their own privileges. Developer accounts remain in the developer console.
- User and role changes are recorded in the activity log without password values.

## Choosing permissions

**Access Dashboard** allows entry to the dashboard. Each of its twelve cards has a separate permission. Use **Select group**, **Clear group**, **Select all**, or **Clear all** to adjust the checkboxes, then save.

**Access Reports Module** allows entry to Reports. Select the corresponding view permission for each report card. **Download Reports (Global)** additionally permits exports; PDF bundles contain only reports the user may view.

Seminars, Extra Classes, and Visiting Teachers can be granted to custom roles. Their respective Manage permissions include their existing module workflows, including attendance, enrollment, and payments where applicable.

Admin and Super Admin are protected system roles with full application access. Their permission screens are read-only. Use custom roles to restrict features or individual cards. Developer is a protected role for the developer console.

After login, users open the first module they may access. Users with no module permissions open their profile.

## Dashboard corrections

- Student dues use current calculated balances rather than the stored opening-due field.
- Student and teacher counts include all qualifying records; their preview lists show up to five.
- Teacher salary payments are included in the expense breakdown as well as the expense total.
- Unconfirmed cheques are excluded from revenue. Viewing the dashboard does not confirm cheques; the existing `cheques:auto-pass` scheduled command handles automatic confirmation.
- Custom dates are validated, and chart titles reflect the selected range.

## Installation and verification

Run `php artisan migrate` when deploying these changes. The access-permissions migration adds missing permission records without reseeding accounts or changing existing role assignments. It has been applied in the current local workspace.

Verification on 19 October 2026:

- `php artisan test`: 110 tests passed, 818 assertions, using an isolated in-memory SQLite database.
- `npm run build`: passed.
- `php artisan view:cache`, PHP syntax checks, and `git diff --check`: passed.
- Browser checks used a separate temporary database: administrator login, user editing with password preservation, permission group selection/clearing, custom-date controls, restricted dashboard visibility, and direct-route denial.
- No real SMS or email was sent during verification.
