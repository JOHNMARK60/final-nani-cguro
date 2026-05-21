# E-Parish — Installation & Access Guide

## What this system is
E-Parish is a parish management system that lets members request certificates, book appointments, upload payment proofs, and view issued e-certificates. Administrators manage queues (appointments, certificates, payments, volunteers) and view audit logs.

---

## 1) Requirements
- PHP 8.1+
- MySQL/MariaDB
- Web server (Laragon / XAMPP works)
- Composer

---

## 2) Install
1. Open the project folder: `E-Parish`.
2. Create your environment file:
   - Copy `/.env.example` → `/.env`
3. Configure database and security settings inside `/.env`.

### Required environment variables
These are used by the application:
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET` (DB connection)
- `DEFAULT_ADMIN_NAME`
- `DEFAULT_ADMIN_EMAIL`
- `DEFAULT_ADMIN_PASSWORD`
- `ADMIN_REGISTRATION_CODE`

> Admin self-registration is protected by **ADMIN_REGISTRATION_CODE**. Only users who know this code can create an account with role = `admin`.

---

## 3) Install dependencies
```bash
composer install
```

---

## 4) First run / migrations
On first request, the system boots and ensures database/migrations are applied (see existing bootstrap/migration behavior in the codebase).

Then:
- A **default admin** is seeded from your `.env` when there is no active admin yet.

---

## 5) How to access the system
### Member access
- Login URL (public landing page):
  - `http://localhost/E-Parish/`
- Register as **Parish Member** (no invite code required).

### Admin access
1. There are two ways to get admin access:

   **A) Default seeded admin (recommended for first setup)**
   - Username: the part before `@` in `DEFAULT_ADMIN_EMAIL`, for example `admin` if the email is `admin@eparish.local`
   - Password: `DEFAULT_ADMIN_PASSWORD`

   **B) Admin self-registration using secret code**
   - Go to: `http://localhost/E-Parish/`
   - Click **Create Account**
   - Select **Administrator** in **Register as**
   - Enter the **Admin invite code** (this is `ADMIN_REGISTRATION_CODE`)
   - Submit the form

2. Admin dashboard is then available after login.

---

## 6) Notes / security
- The **admin invite code** is validated using a constant-time comparison (`hash_equals`).
- Passwords are hashed with `password_hash`.
- Registration and login are protected by CSRF tokens.

---

## 7) Troubleshooting
- If you cannot log in, verify:
  - `.env` database settings
  - `.env` admin variables (`DEFAULT_ADMIN_*`)
  - `ADMIN_REGISTRATION_CODE` (for admin self-registration)
- If pages redirect immediately, ensure you are logging in with an **active admin** account.

