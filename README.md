# FinanceFlow API

A RESTful API for personal finance management built with Laravel.

## Requirements

* PHP 8.3 or higher
* Composer
* SQLite, MySQL, or PostgreSQL database

## Features

* **Authentication:** Token-based authentication using Laravel Sanctum.
* **Account Management:** Multiple cash, card, or bank account wallets.
* **Transactions:** Support for incomes, expenses, and internal transfers between accounts.
* **Categories & Tags:** Customizable categorization and tagging for transactions.
* **Budgets:** Set spending limits per category for specific periods (monthly/yearly).
* **Savings Goals:** Goal tracking with dedicated deposit history.
* **Recurring Transactions:** Automation templates for periodic transactions.
* **Reports & Dashboard:** General balance statistics, monthly charts, and aggregated category breakdowns.
* **API Documentation:** Interactive documentation using Swagger UI.

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd financeflow-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment variables:
   ```bash
   cp .env.example .env
   ```
   Open the `.env` file and configure your database settings. By default, the application is pre-configured to use SQLite.

4. Generate the application key:
   ```bash
   php artisan key:generate
   ```

5. Run database migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

6. Generate Swagger documentation:
   ```bash
   php artisan l5-swagger:generate
   ```

7. Start the local development server:
   ```bash
   php artisan serve
   ```

The API will be available at `http://127.0.0.1:8000`.

## API Documentation

The interactive Swagger UI documentation is accessible at:
`http://127.0.0.1:8000/api/documentation`

## Development Tools

### Running Tests

To run the PHPUnit test suite:
```bash
php artisan test
```

### Static Analysis

To run PHPStan static analysis:
```bash
vendor/bin/phpstan analyse --memory-limit=-1
```

## License

This project is open-sourced software licensed under the MIT license.
