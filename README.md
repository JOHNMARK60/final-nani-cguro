# E-Parish

E-Parish is a parish management system for members and administrators. It handles parish services, appointments, certificate requests, volunteer records, admin queues, audit logs, and secure file access in one place.

## User Guide

### What a user can do
- Create an account and sign in.
- Request parish certificates with near/far delivery guidance.
- Book, update, and delete appointments.
- Submit and monitor Philippine Peso payments.
- Upload proof of payment and print payment receipts.
- View issued e-certificates after linked payment is verified.
- View volunteer records and incentives.
- Update profile information and password.
- Upload profile photos and request attachments.

### User features
- Landing page with modal sign-in and registration.
- User dashboard with request summary.
- Certificate request form and tracking.
- E-certificate access gate for far members after verified payment.
- Walk-in pickup guidance for members near the parish.
- Appointment request workflow.
- Peso payment monitoring page with Cash, GCash, and Bank Transfer options.
- Printable payment receipt page.
- Account settings page.
- Volunteer page.
- Password reset flow.

## Admin Guide

### What an admin can do
- Sign in with an admin account.
- View and process certificate requests.
- Issue walk-in certificates or locked e-certificates from the admin queue.
- View and process appointment requests.
- Verify and reject Philippine Peso payments.
- View and process volunteer requests.
- Manage other admin accounts.
- View audit logs and status history.
- Review download and file access activity.

### Admin features
- Admin dashboard.
- Admin management page.
- Certificate queue with search, filter, and pagination.
- Digital certificate issuance for Baptismal, Confirmation, Marriage, and Death certificates.
- Appointment queue with search, filter, and pagination.
- Payment queue with search, filter, pagination, proof preview, and revenue totals.
- Volunteer queue with search, filter, and pagination.
- Audit log page with filtering and pagination.
- Approval and rejection workflows.

## Current Features

- Auto database creation.
- Auto migration/bootstrap.
- Default admin seeding from `.env`.
- PDO-only database access.
- OOP models, controllers, services, and security helpers.
- CSRF protection.
- Password hashing.
- Password reset flow.
- Role-based access control.
- Admin account management.
- Appointment and certificate history tracking.
- Audit logging.
- Secure file preview and download routes.
- Secure proof-of-payment preview and download routes.
- Tailwind CSS responsive UI with a modern sidebar/topbar shell.
- Mobile bottom navigation for dashboard pages.
- Header notification dropdown and account logout dropdown.
- SweetAlert2 toast feedback and confirmation prompts.
- Philippine Peso payment records, proof uploads, admin verification, and printable receipts.
- Certificate templates and receipt templates optimized for browser printing.
- Poppins typography.
- Responsive landing page.
- Modal sign-in and registration.
- File upload handling.
- SMTP mail abstraction with log fallback.

## Suggested Improvements

- Add real SMTP credentials for production mail delivery.
- Add seeded demo data for local testing.
- Add richer dashboard charts and date range summaries.
- Add full pagination/search to user-side lists as well.
- Add email templates for all approval and rejection events.
- Add stronger per-field validation feedback on all forms.
- Add audit log export.
- Add tests for controller routes with a real database test environment.
- Add soft delete support for some admin records if needed.
- Add notifications inside the app for request updates.

## Project Structure

- `classes/` - Models, controllers, services, security, and core classes.
- `config/` - App bootstrap and database setup.
- `controllers/` - Request handlers.
- `database/migrations/` - Schema creation and updates.
- `includes/` - Shared UI helpers.
- `views/` - User and admin pages.
- `public/` - Web entry point.
- `tests/` - Sample test files.

## Setup

1. Copy `.env.example` to `.env`.
2. Set your database credentials in `.env`.
3. Set these values in `.env`:
   - `DEFAULT_ADMIN_NAME`
   - `DEFAULT_ADMIN_EMAIL`
   - `DEFAULT_ADMIN_PASSWORD`
   - `ADMIN_REGISTRATION_CODE`
   - SMTP variables if you want mail delivery
4. Run Composer install:
   ```bash
   composer install
   ```
5. Run PHPUnit:
   ```bash
   vendor/bin/phpunit
   ```
6. Open the project through Laragon/XAMPP at:
   `http://localhost/E-Parish`

## Notes

- Use the admin invite code only if you want admin self-registration enabled.
- Default admin is only seeded when no active admin exists.
- The project is built for a local PHP + MySQL stack such as Laragon or XAMPP.
