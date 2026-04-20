# AmbatuWork

Website for student collaboration platform

---

## Overview

This project uses a **Laravel-first stack** for the backend, with Docker-based local development.

For the MVP, we decided to keep the setup lean and focused:

- **Laravel** for the application backend
- **Laravel Sail** for local Docker development
- **PostgreSQL** for the database
- **Redis** for cache / queue support
- **Docker** as the runtime
- **Nginx** is planned for the broader server architecture, but for local development Sail is enough

We intentionally **ignored MinIO for now** to keep the first setup simple.

---

## Chosen Stack

### Local Development
- Laravel
- Laravel Sail
- PostgreSQL
- Redis
- Docker

### Production Direction
Later, for server deployment, the cleaner setup is:

- Nginx
- Laravel PHP-FPM app container
- PostgreSQL
- Redis

Important:

- **Use Sail for local development**
- **Do not treat Sail as the final production deployment setup**

---

## Why This Stack

This stack is a good fit for the MVP because:

- Laravel gives us fast backend development
- PostgreSQL is strong for relational app data
- Redis is useful for cache and queues
- Sail removes the need to manually create Docker containers for each service
- Docker makes setup reproducible for other developers

---

## Product Context

The app is a **student collaboration app**.

The general direction discussed was:

- Web app can use **Laravel Blade**
- If interactivity grows, **Livewire** is the likely next step
- Android app can use **Jetpack Compose**
- Android talks to Laravel through API endpoints

For now, we focused only on the **backend foundation**.

---

## Architecture Direction

A clean architecture for this project:

- `routes/web.php` for web routes
- `routes/api.php` for Android / mobile API routes
- shared domain logic in services / actions / models

---

## Prerequisites

Before starting, make sure you already have:

- Docker installed
- Docker running

You do **not** need:

- PostgreSQL installed directly on the machine
- Redis installed directly on the machine
- existing PostgreSQL or Redis containers

Sail will create and run the needed containers for you.

---

## Important Concept: Sail Creates the Containers

You do **not** manually start PostgreSQL or Redis first.

The flow is:

1. Create Laravel project
2. Install Sail
3. Run Sail setup with PostgreSQL and Redis
4. Start Sail
5. Sail creates and runs the app, PostgreSQL, and Redis containers

---

# Setup From Scratch

## 1. Create a new Laravel project

If Composer is not installed locally, use the Composer Docker image:

```bash
docker run --rm -it \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  composer create-project laravel/laravel ambatuwork-web
````

Then move into the project:

```bash
cd ambatuwork-web
```

---

## 2. Install project dependencies if needed

If the project already exists but dependencies are not installed:

```bash
docker run --rm -it \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs
```

---

## 3. Install Laravel Sail

This step is required before the `sail:install` command exists.

```bash
docker run --rm -it \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  composer require laravel/sail --dev
```

---

## 4. Install Sail configuration with PostgreSQL and Redis

```bash
docker run --rm -it \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  php artisan sail:install --with=pgsql,redis
```

This generates the Docker setup for:

* Laravel app container
* PostgreSQL container
* Redis container

---

## 5. Start Sail

```bash
./vendor/bin/sail up -d
```

To verify:

```bash
docker ps
```

You should see containers related to:

* Laravel app
* PostgreSQL
* Redis

---

## 6. Configure `.env`

A sensible local `.env` setup:

```env
APP_NAME=AmbatuWork
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
```

Notes:

* `DB_HOST=pgsql` uses the Sail service name
* `REDIS_HOST=redis` uses the Sail service name
* using Redis from day one is a good default for cache and queues

---

## 7. Generate the application key

```bash
./vendor/bin/sail artisan key:generate
```

---

## 8. Run migrations

```bash
./vendor/bin/sail artisan migrate
```

If using database sessions:

```bash
./vendor/bin/sail artisan session:table
./vendor/bin/sail artisan migrate
```

---

## 9. Open the app

By default:

```text
http://localhost
```

---

# Common Commands

## Start containers

```bash
./vendor/bin/sail up -d
```

## Stop containers

```bash
./vendor/bin/sail down
```

## Restart containers

```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

## Check running containers

```bash
docker ps
```

## Open shell inside app container

```bash
./vendor/bin/sail shell
```

## Run artisan commands

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan make:model Board -m
./vendor/bin/sail artisan make:controller BoardController
```

## Run Composer inside the app container

```bash
./vendor/bin/sail composer require laravel/sanctum
```

## Run tests

```bash
./vendor/bin/sail test
```

---

# Port Mapping

## Default Behavior

By default, the Sail app usually maps the app container port `80` to host port `80`.

That means the app is reachable at:

```text
http://server-ip
```

---

## Changing Host Port to 8081

Yes, this is supported.

The goal is:

* container listens on port `80`
* server exposes port `8081`

### Recommended way

Add this to `.env`:

```env
APP_PORT=8081
```

Then restart Sail:

```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

Now the app should be reachable at:

```text
http://server-ip:8081
```

Also update:

```env
APP_URL=http://server-ip:8081
```

---

## Verify Port Mapping

```bash
docker ps
```

You should see something like:

```text
0.0.0.0:8081->80/tcp
```

---

## If `APP_PORT` Does Not Work

Check the generated `compose.yaml` or `docker-compose.yml`.

Look for the app service and its `ports` section.

Good:

```yaml
ports:
  - '${APP_PORT:-80}:80'
```

Hardcoded version:

```yaml
ports:
  - '80:80'
```

You can change that to:

```yaml
ports:
  - '8081:80'
```

Or better:

```yaml
ports:
  - '${APP_PORT:-80}:80'
```

---

# Things To Consider When Changing Ports

## 1. Port Conflict

Make sure nothing else is using `8081`:

```bash
ss -tulpn | grep 8081
```

If another service is already using it, Docker will fail to bind that port.

---

## 2. Firewall Rules

If the server is public, make sure the firewall allows the port:

* `ufw`
* `firewalld`
* iptables
* VPS / cloud firewall rules

---

## 3. APP_URL Must Match

If Laravel is accessed through `:8081`, set:

```env
APP_URL=http://your-domain-or-ip:8081
```

This helps avoid issues with generated URLs and redirects.

---

## 4. Public Exposure

If this server hosts multiple applications, exposing lots of random ports publicly is not ideal.

A better long-term pattern is:

* host-level Nginx listens on `80/443`
* Nginx reverse proxies to internal app ports like `8081`

For example:

* `app.example.com -> 127.0.0.1:8081`

This is cleaner and easier for SSL.

---

# Troubleshooting

## Problem: `There are no commands defined in the "sail" namespace`

### Cause

Sail was not installed yet.

### Fix

Install Sail first:

```bash
docker run --rm -it \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  composer require laravel/sail --dev
```

Then run:

```bash
docker run --rm -it \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  php artisan sail:install --with=pgsql,redis
```

---

## Problem: `Permission denied` for `.env` or `storage/logs/laravel.log`

### Cause

File ownership / permissions mismatch.

This often happens when files are created by one user and Sail tries to write with another.

### Fix

If running as root:

```bash
chown -R root:root .
chmod -R ug+rw storage bootstrap/cache
```

If needed, apply a stricter fix:

```bash
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
chmod 664 .env
touch storage/logs/laravel.log
```

Then retry:

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

---

## Problem: App does not open after changing port

### Checklist

Check containers:

```bash
docker ps
```

Check if port is listening:

```bash
ss -tulpn | grep 8081
```

Test locally on the server:

```bash
curl http://127.0.0.1:8081
```

Check firewall rules if testing from outside the server.

---

# Recommended Development Workflow

A practical flow during development:

1. Start Sail
2. Work on backend features
3. Run migrations
4. Test routes / APIs
5. Keep web and mobile API concerns separate

Good habits:

* use `web.php` for browser pages
* use `api.php` for Android
* keep business logic out of controllers where possible
* use Redis from early on
* keep the MVP simple

---

# Useful Cheat Sheet

## Sail Command Alias

Optional shell alias:

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Then you can use:

```bash
sail up -d
sail artisan migrate
sail test
```

---

## Quick Reset

Stop everything:

```bash
./vendor/bin/sail down
```

Start again:

```bash
./vendor/bin/sail up -d
```

---

## Rebuild Containers

If Docker config changes:

```bash
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

---

## Logs

View logs:

```bash
./vendor/bin/sail logs
```

Follow logs:

```bash
./vendor/bin/sail logs -f
```

---

## Database Access

Open PostgreSQL shell:

```bash
./vendor/bin/sail psql
```

---

## Redis Access

Open Redis CLI:

```bash
./vendor/bin/sail redis
```

---

# Final Notes

For this project, the agreed direction is:

* start simple
* use Laravel Sail locally
* use PostgreSQL and Redis from day one
* keep the MVP focused
* postpone extra infrastructure until it is actually needed

Current scope:

* Laravel backend
* PostgreSQL
* Redis
* Docker / Sail
* no MinIO yet
* no advanced production stack yet

Longer-term direction:

* Blade for web
* API routes for Android
* Livewire later if web interactivity increases
* separate production Docker setup with Nginx + PHP-FPM + PostgreSQL + Redis

