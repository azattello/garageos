# Проверка GarageOS

## Локальный набор

Запускайте команды из `GarageOS-app`:

```powershell
php artisan test
npx tsc -p tsconfig.json --noEmit
npm run build
composer validate --no-check-publish
composer audit
php artisan migrate:status
```

Проверка 07.10.2026: backend — 114 passed / 1220 assertions; TypeScript, Vite production build и Composer validation прошли; `composer audit` не нашёл advisories; все 34 миграции локальной SQLite отмечены `Ran`.

## Release gate

Feature suite работает на SQLite in-memory и не подтверждает совместимость с production MySQL/MariaDB. На 07.10.2026 PHP 8.3.33 и `pdo_mysql` доступны, но `127.0.0.1:3306` не отвечает и Docker daemon не запущен. До клиентского rollout требуется staging на целевой MySQL 8/MariaDB: применить миграции, пройти заказ/платёж/фото smoke workflow и провести backup/restore rehearsal.

Проверки SMTP, HTTPS, очереди, cron и private storage permissions требуют production-like окружения и не считаются пройденными по локальному build/test.

Test.