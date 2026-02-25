# PIM2 - Minimal Product Information Management

A clean and minimal Product Information Management (PIM) starter built with **PHP + MySQL + HTML/CSS/JS**.

It is designed for your Excel workflow with focus on these two main product tabs:
- `AQUADANA`
- `SIGDETSØDT`

Other tabs can also be imported for shipping/calculations/extra information.

## 1) Requirements (Windows + XAMPP)
- XAMPP (Apache + MySQL)
- PHP 8.1+
- Optional: Composer (only if you want native `.xlsx` import using PhpSpreadsheet)

## 2) Quick Setup
1. Place this project in `xampp/htdocs/PIM2`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin and run `database/schema.sql`.
4. Visit: `http://localhost/PIM2/public/index.php`

## 3) Importing Data from Excel
### Easiest method (recommended)
Export each important Excel tab to CSV and import:
- Save tab `AQUADANA` as CSV
- Save tab `SIGDETSØDT` as CSV
- Use `Import` page in the app

The import screen now runs in chunks and shows progress (`processedRows`, `totalRows`, percentage), which is safer for larger files.

### Optional direct XLSX import
If you want direct `.xlsx` support:
1. Open terminal in project root.
2. Run: `composer require phpoffice/phpspreadsheet`
3. The app import endpoint can then read `.xlsx` files.

## 4) GitHub (for testing and live collaboration)
In project root:
```bash
git init
git add .
git commit -m "Initial PIM2 setup"
git branch -M main
git remote add origin https://github.com/YOUR-USER/YOUR-REPO.git
git push -u origin main
```

## 5) Live Deployment (Beginner-friendly: Railway + GitHub)
GitHub alone cannot run PHP/MySQL. Use Railway to run the app and database.

### Step A: Push project to GitHub
Use the Git commands above so Railway can read your repo.

### Step B: Create Railway project
1. Go to Railway and sign in.
2. Click **New Project** → **Deploy from GitHub repo**.
3. Select your `PIM2` repository.

### Step C: Add MySQL in Railway
1. In the same Railway project, click **New** → **Database** → **MySQL**.
2. Open MySQL service and copy connection values:
	- Host
	- Port
	- Database name
	- Username
	- Password

### Step D: Add environment variables to app service
In the deployed app service variables, add:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Use the values from your Railway MySQL service.

### Step E: Create tables in Railway MySQL
Run SQL from `database/schema.sql` in Railway MySQL query tool.

### Step F: Open live app
1. Open deployed app domain from Railway.
2. Visit `/public/index.php`.
3. Use `/public/import.php` to import your CSV files.

## 6) Project Structure
- `public/` UI pages and static assets
- `src/api/` backend endpoints
- `src/services/` import + repository logic
- `database/schema.sql` MySQL schema

## 7) Environment Variables
Local and cloud both use these variables:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

See `.env.example` for defaults.

## 8) Next Step
After your first import works, we can map your real Excel columns exactly to your business terms.
