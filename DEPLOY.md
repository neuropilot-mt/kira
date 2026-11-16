# Деплой на Laravel Cloud

Сайт — одно Laravel-приложение. Главная отдаёт готовый `resources/site/index.html`, стили и скрипт лежат в `public/`. Админка на Blade: `/admin`. Фото хранятся не на диске сервера, а в Object Storage: диск приложения сбрасывается при каждом деплое.

Нужны аккаунт [Laravel Cloud](https://cloud.laravel.com), репозиторий на GitHub, GitLab или Bitbucket и PHP 8.3+. В проекте стоит Laravel 13.

## 1. Отправьте код в Git

Закоммитьте проект и запушьте ветку, с которой Cloud будет собирать приложение. Файл `.env` в репозиторий не входит.

## 2. Создайте приложение

1. На главной Laravel Cloud нажмите **+ New application**.
2. Подключите GitHub, GitLab или Bitbucket и дайте доступ к этому репозиторию.
3. Выберите репозиторий, имя и регион. Нажмите **Create Application**.
4. В настройках окружения оставьте runtime **PHP** и версию **8.4** (или 8.3).

## 3. Команды сборки и деплоя

Build command:

```sh
composer install --no-dev
npm ci --audit false
npm run build
php artisan optimize
```

`npm run build` собирает CSS публичного сайта в `public/styles.css`. `php artisan optimize` кэширует конфигурацию внутрь сборки, поэтому база, бакет и переменные должны быть подключены до этого деплоя.

Deploy command:

```sh
php artisan migrate --force
php artisan db:seed --force
```

Сид при каждом деплое обновляет администратора из `ADMIN_EMAIL` и `ADMIN_PASSWORD`.

Не добавляйте `php artisan storage:link`, `php artisan optimize:clear`, `queue:restart` и `horizon:terminate`. Ссылку на `storage` Cloud не сохраняет, очереди он перезапускает сам.

## 4. База

На схеме окружения нажмите **Add database** и создайте MySQL или Serverless Postgres в том же регионе, что и приложение. SQLite на Cloud не живёт: файловая система эфемерная.

После подключения Cloud сам записывает `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` и остальные поля. Сессии и кэш уже смотрят в базу (`SESSION_DRIVER=database`, `CACHE_STORE=database`), отдельно их переключать не нужно.

## 5. Хранилище фото

На схеме окружения нажмите **Add bucket**, тип **Laravel Object Storage**, и сделайте бакет диском по умолчанию. Cloud выставит `FILESYSTEM_DISK` и ключи S3-совместимого бакета (Cloudflare R2).

Пакет `league/flysystem-aws-s3-v3` уже есть в зависимостях. Файлы читаются через приложение по адресам `/photos/originals/...` и `/photos/previews/...`, публичная ссылка бакета не нужна.

## 6. Переменные

В окружении задайте:

| Переменная | Значение |
| --- | --- |
| `APP_KEY` | вывод `php artisan key:generate --show` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | адрес приложения в Cloud, вместе с `https://` |
| `ADMIN_EMAIL` | почта входа в админку |
| `ADMIN_PASSWORD` | пароль входа |

`APP_KEY` сгенерируйте локально и вставьте в панель. Не коммитьте его.

Если переменную, базу или бакет добавили после сборки, нажмите Deploy ещё раз: кэш конфигурации собирается на этапе build.

## 7. Первый запуск

Откройте `https://ваш-домен/admin`, войдите почтой и паролем из переменных. Загрузите JPEG, PNG, WebP или GIF до 20 МБ. Имя файла `hero` или галочка «Портрет на главной» ставит кадр в большой портрет. Остальные фото идут в «Избранные работы». На сайте показывается уменьшенная копия, клик открывает загруженный оригинал.

HEIC с телефона админка не принимает: на сервере нет macOS-утилиты, которая конвертировала его локально. Перед загрузкой сохраните кадр как JPEG.

Камера часто снимает JPEG больше 2 МБ, а PHP по умолчанию отклоняет такие файлы ещё до Laravel. В `public/.user.ini` лимит поднят до 40 МБ. Если после деплоя большая фотография снова не загружается, в настройках PHP-окружения поставьте `upload_max_filesize=40M` и `post_max_size=48M`. Локальный `php artisan serve` этот файл не читает: запускайте его так:

```sh
php -d upload_max_filesize=40M -d post_max_size=48M -d memory_limit=512M artisan serve
```
