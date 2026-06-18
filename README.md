# EduAI Graduation Project

This repository is organized into three parts:

- `Website/` - Laravel web platform and backend API.
- `MobileApp/` - NativePHP mobile application.
- `AI/` - AI service code and AI-related reference files.

Generated files are intentionally excluded from Git, including APK/AAB files, build output, `vendor`, `node_modules`, caches, logs, `.env`, and signing keys.

## Local Setup

Install dependencies inside each Laravel app separately:

```bash
cd Website
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

```bash
cd ../MobileApp
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
```
