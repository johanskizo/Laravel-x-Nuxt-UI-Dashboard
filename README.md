# Boilerplate — Modular Laravel + Vue Nuxt UI Admin

Repository: https://github.com/johanskizo/Laravel-x-Nuxt-UI-Dashboard

This repository is a modular Laravel + Vue boilerplate intended to speed up building administrative dashboards and domain modules. The codebase follows a modular structure under `Modules/` so each feature can contain its own backend controllers, models, frontend pages, routes and menu definitions.

**This README** documents how to set up the project locally, where to find important pieces of the codebase, and how to extend it with new modules.

**Stack summary**

- **Backend:** PHP (Laravel)
- **Frontend:** Vue 3 with Vite (Nuxt UI components used in pages)
- **Modular system:** Per-module resources under `Modules/*/Resources`
- **Tooling:** Composer, npm/yarn, Vite, PHPUnit

**Main modules included**

- Authentication
- Dashboard
- User
- Profile
- Privilege (roles & permissions)

**Project layout (high level)**

- **app/**: Laravel app code (Models, Controllers, Providers)
- **bootstrap/**: Application bootstrap files
- **config/**: Configuration
- **Modules/**: Feature modules (each module contains its own Resources, Http, Providers, etc.)
- **public/**: Document root (where to point your webserver)
- **resources/js/src/**: Shared frontend utilities, network instance, components, layouts
- **resources/**: Views, assets, language files
- **routes/**: Laravel route files (web.php, api.php)
- **vendor/**: Composer dependencies

## Quickstart (Local Development)

These steps assume Windows/XAMPP, PHP 7.4 (workspace shows PHP 7.4.33), Composer, Node.js & npm installed.

1. Clone and enter repository

```bash
git clone https://github.com/johanskizo/Laravel-x-Nuxt-UI-Dashboard.git boilerplate
cd boilerplate
```

2. Install backend dependencies

```bash
composer install
cp .env.example .env
# Edit .env and set DB_* and APP_URL, VITE_APP_URL as needed
php artisan key:generate
```

3. Database setup

- Create a database (e.g. `boilerplate`) and update `.env` with `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

```bash
php artisan migrate --seed
```

4. Install frontend dependencies and start Vite

```bash
npm install
# Development: runs Vite dev server (hot reload)
npm run dev
```

5. Serve the backend

- Option A — built-in Laravel server (for quick local dev):

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

- Option B — XAMPP / Apache
  - Set your virtual host/document root to the project `public/` folder
  - Ensure `.env` APP_URL matches the host

Visit `http://127.0.0.1:8000` or your configured host.

## Development Details

- Frontend pages for modules: `Modules/<ModuleName>/Resources/js/pages`.
- Frontend route definitions (SPA) for each module live in `Modules/*/Resources/js/routes.js`.
- Menu entries for the global navigation: `Modules/*/Resources/js/menus.js`.
- Shared frontend code (network instance, components, layout) is under `resources/js/src/`.
- Many Vue files use a consistent comment and section pattern to ease maintainability — follow those conventions when adding pages.

## Testing

- Backend tests: run PHPUnit

```bash
./vendor/bin/phpunit
# or
php artisan test
```

- Frontend tests: check `package.json` for scripts (may use Vitest/Jest depending on configuration).

## Build & Production

- Build production frontend assets:

```bash
npm run build
```

- Ensure production `.env` values are set correctly (APP*ENV, APP_URL, DB*\*, cache/drivers etc.).
- Migrate and seed production DB as needed.

## Adding a Module

The recommended way to scaffold a new module is using the nwidart/laravel-modules Artisan command. This creates a standard module skeleton under `Modules/YourModule`.

```bash
php artisan module:make YourModule
```

After scaffolding, perform these common steps:

1. Backend
   - Add controllers, models and other backend code under the generated folders (`Modules/YourModule/Http/Controllers`, `Modules/YourModule/Models`, etc.).
   - Add migrations under `Modules/YourModule/Database/Migrations` and run them.

2. Frontend
   - Add Vue pages under `Modules/YourModule/Resources/js/pages`.
   - Export frontend routes in `Modules/YourModule/Resources/js/routes.js` (follow existing modules as examples).
   - Add menu entries in `Modules/YourModule/Resources/js/menus.js` so the global navigation includes the module.

3. Register/run module tasks
   - If your installation provides module-specific migration/seed commands, run them (example):

```bash
php artisan module:migrate YourModule
php artisan module:seed YourModule
```

    - Otherwise run the global migrations:

```bash
php artisan migrate
```

## Notes & tips

- Stubs for scaffolding are available in `stubs/nwidart-stubs` — you can customize those templates for your preferred module layout.
- Review existing modules under `Modules/` to copy patterns for API endpoints, frontend routes, and menu wiring.
- Remember to add permission strings and policies if the module introduces new protected routes or UI actions.

## Troubleshooting & Useful Commands

- Clear caches if you see stale config or view output:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

- If assets fail to load, ensure `npm run dev` (Vite) is running and that the browser can access the Vite client.
- Confirm database connectivity if migrations fail.

## Where to start reading the code

- `resources/js/src/` — shared frontend building blocks
- `Modules/User/Resources/js/pages` — example pages showing structure, routing and API usage
- `Modules/Privilege/Resources/js/pages` — role/permission patterns and examples

## Contributing

- Fork the repo and create a feature branch for contributions.
- Add tests for backend logic and ensure frontend components remain testable.
- Keep module changes isolated to their `Modules/<ModuleName>` folder when possible.

## License & Credits

- Check `composer.json` and `package.json` for third-party library licenses.

If you want, I can also:

- produce a short Quickstart (2–3 commands) for new developers,
- add a troubleshooting FAQ section tailored to errors you encounter, or
- generate a localized (e.g., Indonesian) README variant.

---
