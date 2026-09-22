# TESDA Officials Directory

A responsive officials directory built with Native PHP, MySQL, and Tailwind CSS. It includes a public searchable directory, office detail pages, and a secured administration area for maintaining offices and officials.

## Requirements

- PHP 8.1 or newer with PDO MySQL
- MySQL 8.0 or newer
- Apache or Nginx (or PHP's local development server)

## Installation

1. Copy `.env.example` to `.env` and update the database credentials. Leave `APP_URL` blank to use the current project URL automatically, or set it to a fixed URL.
2. Import `database/schema.sql` into MySQL.
3. Create the first administrator:

   `php scripts/create-admin.php admin@tesda.gov.ph "a-strong-password" "Directory Administrator"`

4. Serve the project root. For local testing:

   `php -S localhost:8000`

5. Open `http://localhost:8000` and use `/admin/login.php` to maintain records.

## Bulk import

On the admin page, choose **Import data** and download the CSV template. Open it in Excel or another spreadsheet app. Keep the header row, fill in one row per official, and repeat the same `office_key` and office details for additional officials in an office. For an office without officials, leave the official columns blank. Save as CSV UTF-8 before uploading.

Imports add new offices and their officials in one transaction. A matching office already in the database stops the import to avoid duplicate records. The upload limit is 5 MB or 1,000 data rows.

The local database was populated from TESDA's public [Central Office](https://www.tesda.gov.ph/directory), [Regional / Provincial Office](https://www.tesda.gov.ph/Directory/Regions), and [Technology Institute](https://www.tesda.gov.ph/Directory/TTI) directory pages on September 21, 2026. The downloaded source pages are in `database/tesda-sources/`. To inspect their parsed counts, run `php scripts/import-tesda-directory.php`; to reapply them to the configured database, run `php scripts/import-tesda-directory.php --apply`. The script matches existing offices and officials and saves changes in a transaction.

Technology institute regions are assigned from their listed locations using `php scripts/assign-tti-regions.php --apply`. This mapping covers all 186 imported institutions and uses the current Negros Island Region and Isabela City classifications. The TESDA directory has no address for the Provincial/City Manpower Development Center; its BARMM assignment follows TESDA's published training center address in Marawi City.

## Included sample data

- TESDA National Capital Region (NCR)
- ANGELINA M. CARREON — Regional Director
- Address, telephone number, and email from the supplied sample

## Security notes

- Change the initial administrator password immediately.
- Use HTTPS in production.
- Keep `.env` outside version control.
- For an internet-facing deployment, add rate limiting at the web-server or reverse-proxy layer.
