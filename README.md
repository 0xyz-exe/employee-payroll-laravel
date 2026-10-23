# Employee Payroll Laravel

Repository: https://github.com/0xyz-exe/employee-payroll-laravel

## Screenshots

### Application Dashboard
![Application dashboard](docs/screenshots/01_dashboard.png)

### Employee Directory & Profiles
![Employee directory](docs/screenshots/02_employees.png)

### Payroll Period Runs
![Payroll runs](docs/screenshots/03_payroll_runs.png)

### Calculation Details & Audit Breakdown
![Payroll breakdown and approval](docs/screenshots/04_payroll_details.png)

### Self-Service Printable Payslip
![Printable payslip](docs/screenshots/05_payslip.png)

### Secure Authentication & Access Control
![Authentication](docs/screenshots/06_login.png)

## Features

- Employee profiles, departments, job positions, employment status, optional account linking.
- Effective-dated base salary, allowances, deductions; daily attendance and overtime inputs.
- Payroll-period calculation with integer minor-unit amounts and saved calculation snapshots.
- Draft → separate-user approval → finalization workflow; finalized entries are not recalculated or edited.
- HR/payroll audit trail for employee, salary-component, attendance, and payroll lifecycle changes.
- Employee-specific payslips, printable from browser; employee users can only access linked, finalized payslips.
- Authorized CSV export for finalized periods; formula-leading text is neutralized.
- Role-based access for `admin`, `hr`, `payroll`, and `employee`.
- No employee records or user credentials are seeded. Demo departments/positions only.

## Payroll formula and scope

`net = max(0, monthly base + effective allowances + configured overtime pay − effective deductions)`.

Base salary is a fixed monthly amount; attendance days and overtime minutes are captured and shown, but attendance does not prorate base pay. Overtime is minutes × `PAYROLL_OVERTIME_MINOR_PER_HOUR`, rounded to the nearest minor unit. Set that rate to zero until an employer-approved formula is configured. All amounts use integer minor units; the currency and minor-unit scale are deployment-specific.

This app is a payroll workflow/calculator, not tax or statutory compliance software. It does not calculate jurisdiction-specific taxes, benefits, withholdings, or legal overtime rates. Validate formulas with qualified local payroll/accounting professionals before operational use. No real employee data is included.

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite by default; PostgreSQL can be configured with the matching PHP PDO driver. PostgreSQL was not exercised in this local build.

## Local setup (Windows)

```powershell
Copy-Item .env.example .env
php C:/Users/wondr/.config/herd-lite/bin/composer.phar install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/register` to create a user. The public registration role defaults to `employee`; no privileged account/password is bundled. Promote a trusted account locally using Tinker:

```powershell
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'your-address@example.com')->firstOrFail();
$user->forceFill(['role' => 'admin'])->save();
```

The seeded departments are Operations and Finance with Staff and Supervisor positions. HR/admin users can add further departments and positions. Create employee records under **Employees**; base salary is required. Link a previously registered employee account by its user ID to allow employee self-service payslips. Grant `hr` or `payroll` roles only to approved staff.

Set `PAYROLL_OVERTIME_MINOR_PER_HOUR` in `.env` only after selecting the employer's verified overtime formula and currency unit. Then clear cached config (`php artisan config:clear`).

## Payroll workflow

1. HR records employees, effective-dated compensation, attendance, and overtime.
2. HR/payroll/admin calculates a non-overlapping draft period (maximum 366 days). Missing effective base salary aborts the transaction.
3. A different payroll/admin user approves the draft.
4. Payroll/admin finalizes the approved period. Payslips and CSV export become available. Finalized figures persist as immutable snapshots; corrections require a separately audited workflow (not implemented).

## Validation

```powershell
php artisan migrate:fresh --seed
php artisan test --compact
npm run build
php C:/Users/wondr/.config/herd-lite/bin/composer.phar validate --no-check-publish
php C:/Users/wondr/.config/herd-lite/bin/composer.phar audit
npm audit --omit=dev
```

## Production notes

Configure HTTPS, secure session settings, database backups, least-privilege staff roles, email/password recovery, retention/access policies for employment data, and jurisdiction-approved payroll formulas before deployment. Never use `.env` or actual payroll data in source control. The default signup flow permits account creation but does not grant staff roles.

## License

MIT (Laravel application scaffolding and project code).
