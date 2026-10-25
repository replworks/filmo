# TECH_STACK.md

## 1. Languages / runtimes / frameworks / libraries

- PHP MUST use version constraint `^8.3`.
- Laravel MUST use version constraint `^13.0`.
- Filament MUST use version constraint `^5.0`.
- `cable8mm/youtube` MUST use version constraint `^6.0`.
- `maatwebsite/excel` MUST be used through the dependency constraint supplied by `cable8mm/youtube`.
- Composer MUST manage PHP dependencies and MUST use `composer.lock` for reproducible installs.
- MySQL MUST use major version 8.
- Redis MUST use version `7.4+` (verify).
- Laravel Eloquent MUST be used for relational database access.
- Laravel Queue and Scheduler MUST be used for scheduled and queued work.
- Laravel HTTP Client MUST be used for Gemini API and web page HTTP requests.
- Node.js MUST use version constraint `^24.0` (verify).
- npm MUST use version constraint `^11.0` (verify).
- Vite MUST use version constraint `^7.0` (verify) for frontend asset builds.
- Tailwind CSS MUST use version constraint `^4.1`.
- PHPUnit MUST use version constraint `^12.0`.
- Laravel Pint MUST use version constraint `^1.0` for PHP linting and formatting.
- Docker MUST build the application image.
- GitHub Actions MUST build the Docker image.
- Coolify MUST deploy the Docker image.

## 2. Framework- or tool-mandated paths and file names only

- `composer.json` MUST declare PHP dependencies and Composer scripts.
- `composer.lock` MUST record the resolved PHP dependencies.
- `package.json` MUST declare Node.js build dependencies and scripts.
- `package-lock.json` MUST record the resolved Node.js dependencies.
- `bootstrap/providers.php` MUST register application service providers as required by Laravel.
- `routes/console.php` MUST define Laravel scheduled commands.
- `.github/workflows/*.yml` MUST contain GitHub Actions workflows.
- `Dockerfile` MUST define the Docker image build.

## 3. Coding conventions specific to the chosen stack

- PHP code MUST follow the Laravel Pint default preset.
- PHP code MUST pass Laravel Pint checks.
- Application dependencies MUST be declared in `composer.json` or `package.json` according to their package manager.
- Frontend assets MUST be built with Vite.

## 4. Testing rules

- PHPUnit MUST run the PHP test suite.
- PHPUnit test files MUST use the `*Test.php` suffix.
- Tests MUST run against MySQL 8.
- Line coverage MUST be at least 50%.

## 5. Configuration rules

- Local environment settings MUST be loaded from `.env`.
- `.env` MUST NOT be committed to source control or included in the Docker image.
- Production secrets MUST be supplied through Coolify environment variables.
- `APP_KEY`, `APP_ENV`, `APP_DEBUG`, and `APP_URL` MUST be configurable through environment variables.
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` MUST configure MySQL access.
- `QUEUE_CONNECTION`, `REDIS_HOST`, `REDIS_PORT`, and `REDIS_PASSWORD` MUST configure Redis queues.
- `YOUTUBE_API_KEY` MUST provide the YouTube Data API credential.
- `GEMINI_API_KEY` MUST provide the Gemini API credential.

## 6. Build, packaging, and release rules

- PHP production dependencies MUST be installed from `composer.lock` with development dependencies excluded.
- Node.js production assets MUST be built with `npm ci` and `npm run build`.
- GitHub Actions MUST build the Docker image from `Dockerfile`.
- GitHub Actions MUST publish the Docker image to a container registry configured for Coolify.
- Coolify MUST deploy the published Docker image.
- The project MUST be distributed as All Rights Reserved.

## 7. Security rules tied to the chosen stack

- Laravel web requests MUST use Laravel's CSRF protection.
- Filament access MUST require Laravel session authentication.
- API credentials MUST be read from environment configuration and MUST NOT be written to source files, logs, or Docker image layers.
- Production MUST set `APP_DEBUG=false`.

## 8. Non-goals

- Docker Compose MUST NOT be used.
- Naver API clients MUST NOT be added for automated person-information collection.
