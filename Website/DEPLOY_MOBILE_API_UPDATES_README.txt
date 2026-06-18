EduAI mobile/API update deploy notes

Upload this zip to the Laravel project root and extract it there.

It updates:
- Main Laravel API routes/controllers for chat history, plans, credits, and normalized job results.
- NativePHP MobileApp service and Livewire screens for UUID sessions, safe generation status handling, chat history, current plan, credits, and upgrade options.
- NativePHP MobileApp tool routes now use one ToolRunner screen. Tool pages no longer create a session in mount and redirect to another page, which prevents the blank/black session screen.

After extracting on the server, run in the main Laravel project root:

php artisan optimize:clear
php artisan view:cache

If your MobileApp is deployed separately, also run inside MobileApp after Composer dependencies are installed:

composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan view:cache

The mobile app API base URL defaults to:
https://ai.eduvoo.com/api/v1

You can override it in MobileApp .env with:
EDUAI_API_BASE_URL=https://your-domain.com/api/v1
