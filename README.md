# Grabber Hub LK — Monorepo

## Structure

```
grabber-hub-lk/
├── api/              # Laravel 11 REST API
├── web/              # Next.js 16 web app
├── flutter/
│   ├── customer/     # Customer mobile app
│   ├── provider/     # Provider mobile app
│   ├── admin/        # Admin mobile app
│   └── shared/       # Shared Dart package (grabber_shared)
├── supabase/         # Supabase config, migrations, edge functions
└── docker-compose.yml
```

## Quick Start

### 1. Start infrastructure
```bash
docker compose up -d
```

### 2. API (Laravel)
```bash
cd api
cp .env.example .env          # edit DB_* values
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve             # http://localhost:8000
```

### 3. Web (Next.js)
```bash
cd web
npm install
npm run dev                   # http://localhost:3000
```

### 4. Flutter apps
```bash
cd flutter/customer
flutter pub get
flutter run

# or provider / admin
```

### 5. Supabase local
```bash
supabase start                # requires Supabase CLI
```

## Services

| Service   | URL                       |
|-----------|---------------------------|
| API       | http://localhost:8000     |
| Web       | http://localhost:3000     |
| Postgres  | localhost:5432            |
| Redis     | localhost:6379            |
| pgAdmin   | http://localhost:5050     |
| Mailpit   | http://localhost:8025     |
| Supabase  | http://localhost:54323    |

## Locales

Supported: `en`, `si`, `ta`, `ar`, `hi`, `zh`, `ja`, `fr`

## Tech Stack

- **API**: PHP 8.5 / Laravel 11 / PostgreSQL / Redis
- **Web**: Next.js 16 / TypeScript / Tailwind / next-intl
- **Mobile**: Flutter 3.11 / Dart / Riverpod / GoRouter
- **Realtime**: Supabase / Laravel Reverb
- **Payments**: WebxPay
