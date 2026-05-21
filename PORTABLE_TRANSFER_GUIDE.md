# E-Parish Portable Transfer Guide

Use this when moving the system to another laptop or cloning it from GitHub.

## Fresh Install on Another Laptop

1. Install Laragon or XAMPP with PHP 8.1+ and MySQL/MariaDB.
2. Put the project folder at `C:\laragon\www\E-Parish` or the equivalent web root.
3. Copy `.env.example` to `.env`.
4. Set these `.env` values for the new laptop:
   - `DB_HOST=localhost`
   - `DB_DATABASE=eparish_db`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` or the local MySQL password
   - `APP_URL=http://localhost/E-Parish`
   - `APP_KEY` to any long random string
   - `DEFAULT_ADMIN_*` values for the first admin account
5. Start Apache/Nginx and MySQL in Laragon/XAMPP.
6. Open `http://localhost/E-Parish`.
7. Optional: apply the neat phpMyAdmin Designer layout:
   ```bash
   php database/apply_phpmyadmin_designer_layout.php
   ```
   The layout coordinates are saved in `database/phpmyadmin_designer_layout.json`, so the ERD arrangement is preserved in GitHub.

The app creates the database, runs all migrations, and seeds the default admin automatically on first load.

## Exact Same Data on Another Laptop

A fresh install gives the same tables, not the same records. To transfer the exact same users, requests, payments, and certificates:

1. In phpMyAdmin on the original laptop, select `eparish_db`.
2. Click **Export**.
3. Choose **Custom** and include structure and data.
4. Save the `.sql` file.
5. On the new laptop, create/import into `eparish_db` using phpMyAdmin **Import**.
6. Copy the `uploads/` folder if uploaded certificate, profile, and payment proof files must still open.
7. Copy the `assets/gallery/` folder if gallery images were added outside GitHub.
8. Run `php database/apply_phpmyadmin_designer_layout.php` if you want the same clean ERD arrangement in phpMyAdmin Designer.

## What Should Not Be Public

Do not push real production passwords, SMTP credentials, or private database exports to a public GitHub repository. Use `.env.example` for safe defaults and keep real `.env` values local when possible.

## Quick Check

After transfer:

- Open `http://localhost/E-Parish`.
- Check that the login page loads.
- Sign in with the default admin.
- Open phpMyAdmin > `eparish_db` > **Designer** and select **E-Parish ERD**.
- Confirm uploaded files display from `uploads/`.
