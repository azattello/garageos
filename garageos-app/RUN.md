# Запуск GarageOS локально

## Требования

- PHP 8.3 или новее
- Composer
- Node.js и npm
- SQLite для локальной базы данных

Проверить версии:

```powershell
php --version
composer --version
node --version
npm --version
```

## Первый запуск

Откройте PowerShell в корне репозитория и перейдите в каталог приложения:

```powershell
cd .\GarageOS-app
```

Если зависимости еще не установлены:

```powershell
composer install
npm install
```

Подготовьте окружение для локальной разработки и SQLite. `.env.example` является production-шаблоном, поэтому задайте в локальном `.env` следующие значения:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
MAIL_MAILER=log
FILESYSTEM_DISK=public
```

Подготовьте окружение и базу данных:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
New-Item -ItemType File -Path .\database\database.sqlite -Force | Out-Null
php artisan migrate
```

Команду `php artisan key:generate` достаточно выполнить один раз для нового `.env`.

Для демонстрационных данных:

```powershell
php artisan db:seed --class=DemoSeeder
```

## Запуск в режиме разработки

Для Windows рекомендуется открыть два окна PowerShell и выполнить команды из каталога `GarageOS-app`.

В первом окне:

```powershell
php artisan serve
```

Во втором окне:

```powershell
npm run dev
```

Откройте приложение:

- http://127.0.0.1:8000
- публичный demo-режим: http://127.0.0.1:8000/demo

Для остановки нажмите `Ctrl+C`.

Для запуска Laravel, Vite, очереди и просмотрщика логов одной командой используйте:

```powershell
composer dev
```

В Windows команда запускается частично: Laravel, Vite и очередь работают, но Laravel Pail завершится с ошибкой, поскольку расширение `pcntl` недоступно. Логи можно смотреть в `storage/logs/laravel.log`.

## Запуск отдельных процессов

Если нужен только frontend или отдельное окно для каждого процесса:

```powershell
# Laravel
php artisan serve

# Vite
npm run dev

# Очередь, при необходимости
php artisan queue:listen --tries=1
```

При таком запуске Laravel будет доступен на `http://127.0.0.1:8000`, а Vite подключится автоматически.

## Production-сборка frontend

```powershell
npm run build
```

## Полезные команды

```powershell
php artisan migrate:status
php artisan route:list
php artisan test
php artisan optimize:clear
```

Если приложение не открывается, сначала проверьте, что команда выполнена из `GarageOS-app`, `.env` содержит `APP_KEY`, а порт `8000` свободен.

## Production deployment checklist

### Поддерживаемая конфигурация

- PHP 8.3+ и Composer 2; PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `gd`, `bcmath`, `xml`, `curl`, `zip`.
- MySQL 8.0+ или MariaDB 10.6+ с `utf8mb4`; отдельная БД и отдельный пользователь с минимальными необходимыми правами.
- Nginx/Apache document root направлен строго в `GarageOS-app/public`; HTTPS обязателен.
- Node.js нужен только для сборки assets; Node server в runtime не нужен.

### Установка / обновление

1. Настроить production `.env` по `.env.example`: уникальный `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, `DB_CONNECTION=mysql` или `mariadb`, DB credentials, SMTP, `APP_TIMEZONE=Asia/Almaty`, `DB_TIMEZONE=+05:00`, `SESSION_SECURE_COOKIE=true`, `FILESYSTEM_DISK=public`. Сгенерировать ключ через `php artisan key:generate` и не менять его при обновлениях.
2. Установить backend/frontend зависимости и собрать assets:

	```sh
	composer install --no-dev --prefer-dist --optimize-autoloader
	npm ci
	npm run build
	```

3. Подготовить writable `storage/` и `bootstrap/cache/`; выполнить `php artisan storage:link`. До открытия public traffic выполнить `php artisan photos:secure-migrate` и убедиться, что команда завершилась без missing files; order photos после миграции выдаются только через authorization routes.
4. Снять backup БД и storage перед обновлением; применить миграции: `php artisan migrate --force`.
5. Прогреть кэш конфигурации/маршрутов/views: `php artisan optimize`; после deploy выполнить `php artisan queue:restart`.
6. Проверить `/up`, login, создание тестового заказа/оплаты, загрузку и чтение фото, отправку email; затем проверить логи Laravel и PHP-FPM.
7. Настроить автоматические backup БД и storage и регулярно проверять восстановление.

### Cron, queue и runtime

В `routes/console.php` зарегистрирована задача `subscriptions:expire`, запускаемая каждые 5 минут; trial/paid hard lock также выполняется синхронно на каждом запросе, поэтому cron не является единственной защитой:

```cron
* * * * * cd /var/www/GarageOS-app && php artisan schedule:run >> /dev/null 2>&1
```

При `QUEUE_CONNECTION=database` запустить постоянный `php artisan queue:work --sleep=3 --tries=3 --timeout=90` через Supervisor/systemd и настроить автоматический restart при deploy. Worker нужен для queued jobs; expiry sweep выполняется scheduler-ом.

### Database compatibility gate

Все migrations и feature tests запускаются на SQLite в автоматическом suite. Проверка 07.10.2026: `pdo_mysql` установлен, но сервер на `127.0.0.1:3306` недоступен; Docker daemon также не запущен. Поэтому MySQL/MariaDB migration smoke-test ещё не подтверждён. Перед первой клиентской установкой выполнить на staging-копии production DB:

```sh
php artisan migrate:status
php artisan migrate --force
php artisan test
```

Установку считать готовой только после успешного migrate/test на целевом MySQL 8 или MariaDB и проверки фактических версий SQL server/PHP extensions.