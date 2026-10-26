# LAMP + Laravel

Локальная dev-среда: PHP 8.4 (Apache) + MySQL 8.4 + RabbitMQ + Laravel 13 (JSON API) с фронтендом Vue 3 + TypeScript (SPA). Приложение — «Dinghy», инструмент для проведения ретроспектив. Основной интерфейс: `https://dinghy.tst/app`, phpMyAdmin — `https://dinghy.tst/phpmyadmin/`, RabbitMQ Management — `http://localhost:15672` (логин/пароль из `.env`).

## Требования

- Linux с Docker и плагином Compose (проверка: `docker compose version`)
- `openssl`, `setfacl`
- Пользователь должен состоять в группе `docker` (запуск без sudo)

> Windows/macOS: использовать WSL2 (Dev Containers) — дальнейшие шаги те же.

## Развёртывание с нуля

### 1. Получить проект и настроить переменные

```bash
cd lamp
cp .env.example .env          # переменные compose (БД, пароли)
cp laravel/.env.example laravel/.env
```

В `laravel/.env` задать:

```ini
APP_NAME=Dinghy
APP_URL=https://dinghy.tst
DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=appdb
DB_USERNAME=appuser
DB_PASSWORD=secret
MAIL_MAILER=log
SESSION_SECURE_COOKIE=true
```

### 2. Сертификаты для HTTPS

```bash
mkdir -p certs
openssl req -x509 -newkey rsa:4096 -nodes -keyout certs/localCA.key \
    -out certs/localCA.crt -days 3650 -subj "/CN=Local Dev Root CA/O=Local Dev"
openssl req -newkey rsa:2048 -nodes -keyout certs/dinghy.tst.key \
    -out certs/dinghy.tst.csr -subj "/CN=dinghy.tst"
openssl x509 -req -in certs/dinghy.tst.csr -CA certs/localCA.crt -CAkey certs/localCA.key \
    -CAcreateserial -out certs/dinghy.tst.crt -days 825 \
    -extfile <(printf "subjectAltName=DNS:dinghy.tst\nbasicConstraints=CA:FALSE")
rm certs/dinghy.tst.csr
```

> ⚠️ Ключи `*.key` — секреты, не коммитьте их (добавьте `certs/` в `.gitignore`).

### 3. Домен в /etc/hosts

```bash
echo '127.0.0.1 dinghy.tst' | sudo tee -a /etc/hosts
```

### 4. Зависимости PHP и фронтенда

Каталог `vendor/` и `node_modules/` в репозиторий не входят:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD/laravel:/app" composer:2 install
docker run --rm -u $(id -u):$(id -g) -v "$PWD/laravel:/app" -w /app node:22 \
    sh -c 'npm ci && npm run build'
```

### 5. Права на записываемые каталоги Laravel

`storage/` и `bootstrap/cache/` должны быть доступны на запись процессу Apache (в контейнере это uid/gid 33). Настраивается через POSIX ACL, без sudo:

```bash
chmod -R ug=rwX,o= laravel/storage laravel/bootstrap/cache
setfacl -R -m g:33:rwX -m u:$(id -u):rwX \
    -m d:u::rwX -m d:g::rwX -m d:g:33:rwX -m d:u:$(id -u):rwX -m d:o::--- \
    laravel/storage laravel/bootstrap/cache
```

> Если до этого контейнер уже создавал файлы, принадлежащие root/uid 33, сначала верните владение себе:
> `docker compose run --rm --no-deps --entrypoint sh web -c 'chown -R $(id -u):$(id -g) /var/www/laravel/storage /var/www/laravel/bootstrap/cache'`

### 6. Сборка и запуск

```bash
docker compose up -d --build
```

### 7. Инициализация Laravel

```bash
docker compose exec -T -u www-data -w /var/www/laravel web php artisan key:generate
docker compose exec -T -u www-data -w /var/www/laravel web php artisan migrate --force
```

### 8. Доверие к локальному CA

Без этого браузер будет ругаться на сертификат:

```bash
# Chromium/Chrome:
certutil -d sql:$HOME/.pki/nssdb -A -t "CT,," -n "Local Dev Root CA" -i certs/localCA.crt
# Firefox: Настройки → Приватность → Сертификаты → Импорт → certs/localCA.crt
```

### 9. Проверка

| URL | Ожидается |
|---|---|
| http://dinghy.tst | редирект на https |
| https://dinghy.tst/app | SPA: вход / регистрация, затем список ретро |
| https://dinghy.tst/app/retros/{id} | доска ретро |
| https://dinghy.tst/phpmyadmin/ | phpMyAdmin (root / пароль из `.env`) |
| http://localhost:15672 | RabbitMQ Management (логин/пароль из `.env`) |

## Повседневная работа

```bash
docker compose ps                     # статус контейнеров
docker compose logs -f web            # логи Apache / PHP
docker compose logs -f queue          # логи очередей (worker)
tail -f laravel/storage/logs/laravel.log   # логи Laravel (в т.ч. письма при MAIL_MAILER=log)
docker compose exec -w /var/www/laravel web php artisan <команда>
docker compose exec -w /var/www/laravel web composer require "<пакет>"
docker compose down                   # остановить
docker compose down -v                # остановить и удалить данные БД и RabbitMQ
```

## Известные проблемы

| Симптом | Решение |
|---|---|
| `touch(): Utime failed` в логах | `docker compose exec -T -u www-data -w /var/www/laravel web php artisan view:clear` |
| HTTP 500, «Table ... doesn't exist» | не выполнены миграции, см. шаг 7 |
| Пустая страница, стили не грузятся | не собран фронтенд, см. шаг 4 |
| Ошибка прав на запись в storage | повторить шаг 5 |
| Браузер ругается на сертификат | не выполнен шаг 8 (или заходили по `localhost`, а не `dinghy.tst`) |
