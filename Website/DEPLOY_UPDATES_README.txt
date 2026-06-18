EduAI update zip deploy notes

Upload this zip to the Laravel project root on the online server, then extract it there so the folders merge with the existing app, resources, routes, and database folders.

After extracting, run:

php artisan migrate --force
php artisan optimize:clear
php artisan view:cache

If uploaded profile photos or covers should be public, make sure storage is linked:

php artisan storage:link

Admin AI settings are available from:

/admin

The settings table migration is included in this zip.
