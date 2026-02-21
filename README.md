# BotCRM Backend — Laravel API

Laravel 12 API с DDD-архитектурой. Ядро платформы: бизнес-логика, AI-оркестрация, интеграция с YClients, управление тенантами, сценариями и диалогами.

## Технологический стек

- **PHP 8.3** / **Laravel 12**
- **PostgreSQL 16** — основная БД
- **Redis 7** — кэш, очереди, WebSocket (Reverb)
- **HashiCorp Vault** — хранение секретов (dev: FakeVault → Redis)
- **OpenAI API** (GPT-4o) — AI + Function Calling
- **OpenAI Whisper** — транскрипция голосовых
- **YClients API** — бронирование
- **Laravel Reverb** — WebSocket для real-time чатов
- **Docker** / **Docker Compose**

## Архитектура

```
Telegram/WhatsApp → Gateway (Node.js) → Backend (Laravel) → OpenAI/YClients
                                              ↕
                                     React SPA (Frontend)
```

Backend — центральный сервис. Принимает сообщения от Gateway, обрабатывает AI-логику, управляет записями через YClients, отдаёт данные во Frontend.

### DDD-структура

```
app/
├── Abstracts/              # Базовые классы (Action, Controller, Resource)
├── Domain/                 # Бизнес-логика (не зависит от фреймворка)
│   ├── Identity/           # Тенанты, авторизация, Vault-секреты
│   ├── Channel/            # Telegram/WhatsApp каналы, webhook
│   ├── Conversation/       # Диалоги, сообщения, режимы, rich media
│   ├── AI/                 # OpenAI, промпты, function calling
│   ├── Booking/            # YClients API (услуги, слоты, записи)
│   ├── Scenario/           # Блок-схемы сценариев, движок
│   └── Billing/            # Подписки, лимиты, usage
├── Adapters/               # Реализации контрактов → внешние API
│   ├── Vault/              # HashiCorp Vault / FakeVault (Redis)
│   ├── OpenAI/             # OpenAI Chat + Whisper
│   ├── YClients/           # YClients HTTP API
│   ├── Gateway/            # Node.js Gateway HTTP client
│   ├── Telegram/           # Telegram Bot API (webhook registration)
│   ├── FileStorage/        # S3 / Local disk
│   ├── Scenario/           # ScenarioEngine (выполнение блок-схем)
│   └── Payment/            # Stripe / ЮKassa
├── Http/                   # Контроллеры, Requests, Resources (по фичам)
│   ├── Auth/
│   ├── Channels/
│   ├── BotSettings/
│   ├── Faq/
│   ├── Scenarios/
│   ├── Conversations/
│   ├── YClientsSettings/
│   ├── Analytics/
│   ├── Billing/
│   ├── Webhook/            # POST /api/bot/incoming (Gateway → Laravel)
│   └── Middleware/
├── Console/Commands/
└── Providers/
    ├── AppServiceProvider.php
    ├── AdapterServiceProvider.php   # Все биндинги Contract → Adapter
    └── EventServiceProvider.php
```

### Ключевые правила

- **Domain** не зависит от Http, Adapters, Framework
- Все Actions: `final class`, extends `AbstractAction`, метод `handle()`
- Все DTO: `final readonly class` в `DataObjects/`
- **Нельзя** вызывать Action из другого Action
- Все зависимости — через constructor injection
- Модели не обращаются к внешним сервисам
- `declare(strict_types=1)` в каждом файле

## Быстрый старт

### Требования

- Docker & Docker Compose v2
- (для Telegram) ngrok / Cloudflare Tunnel на машине с Gateway

### Установка

```bash
# 1. Клонировать
git clone git@github.com:your-org/botcrm-backend.git
cd botcrm-backend

# 2. Скопировать env
cp .env.example .env

# 3. Заполнить ключи в .env:
#    OPENAI_API_KEY=sk-...
#    GATEWAY_SECRET=shared-secret-between-backend-and-gateway
#    GATEWAY_PUBLIC_URL=https://xxx.ngrok-free.app (URL Gateway)

# 4. Запустить инфраструктуру + приложение
docker compose up -d

# 5. Инициализация
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### Docker Compose (инфраструктура)

```yaml
# docker-compose.yml включает:
# - app (PHP-FPM + Laravel)
# - nginx (reverse proxy → app:9000)
# - postgres (16-alpine)
# - redis (7-alpine)
# - vault (HashiCorp, dev mode)
# - queue-worker (php artisan queue:work)
# - reverb (php artisan reverb:start)
# - scheduler (php artisan schedule:work)
```

## Команды

### Artisan

```bash
# Миграции
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan migrate:rollback

# Очереди
docker compose exec app php artisan queue:work --tries=3
docker compose exec app php artisan queue:failed
docker compose exec app php artisan queue:retry all

# WebSocket
docker compose exec app php artisan reverb:start

# Кэш
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache

# Tinker
docker compose exec app php artisan tinker
```

### Тесты

```bash
# Все тесты
docker compose exec app php artisan test

# С покрытием
docker compose exec app php artisan test --coverage

# Конкретный тест
docker compose exec app php artisan test --filter=ProcessIncomingMessageActionTest

# По домену
docker compose exec app php artisan test --testsuite=Domain
```

### Код-стайл

```bash
# Pint (форматирование)
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/pint --test  # только проверка

# PHPStan (статический анализ)
docker compose exec app ./vendor/bin/phpstan analyse

# Всё вместе
docker compose exec app composer check
```

### Полезные команды разработки

```bash
# Создать новый домен (вручную — структура)
mkdir -p app/Domain/NewDomain/{Actions,Models,DataObjects,Enums,Events,Contracts,Exceptions}

# Создать миграцию
docker compose exec app php artisan make:migration create_table_name_table

# Просмотр роутов
docker compose exec app php artisan route:list --path=api

# Логи в реальном времени
docker compose logs -f app
docker compose logs -f queue-worker
```

## Переменные окружения

| Переменная | Описание | Пример |
|---|---|---|
| `OPENAI_API_KEY` | API-ключ OpenAI | `sk-...` |
| `GATEWAY_SECRET` | Shared secret для Gateway ↔ Backend | `random-string` |
| `GATEWAY_PUBLIC_URL` | Публичный URL Gateway | `https://gw.yourservice.com` |
| `VAULT_DRIVER` | `fake` (Redis) или `vault` (HashiCorp) | `fake` |
| `VAULT_URL` | URL Vault сервера | `http://vault:8200` |
| `VAULT_TOKEN` | Токен доступа к Vault | `dev-root-token` |
| `FILESYSTEM_DISK` | Хранилище файлов: `local` / `s3` | `local` |
| `REVERB_APP_ID` | ID приложения Reverb | `botcrm` |
| `REVERB_APP_KEY` | Ключ Reverb | `reverb-key` |
| `REVERB_APP_SECRET` | Секрет Reverb | `reverb-secret` |

## API Endpoints (краткая карта)

```
Auth:           POST /api/auth/{register,login,logout}  GET /api/auth/me
Channels:       GET|POST /api/channels  PUT|DELETE /api/channels/{id}
                POST /api/channels/{id}/{activate,deactivate}
Bot Settings:   GET|PUT /api/bot/settings
FAQ:            GET|POST /api/bot/faq  PUT|DELETE /api/bot/faq/{id}
Scenarios:      GET|POST /api/scenarios  GET|PUT|DELETE /api/scenarios/{id}
                POST /api/scenarios/{id}/{toggle,duplicate}
Conversations:  GET /api/conversations  GET /api/conversations/{id}
                GET /api/conversations/{id}/messages
                POST /api/conversations/{id}/{takeover,release,toggle-ai,send,close}
YClients:       GET|PUT /api/yclients/settings
                GET /api/yclients/branches  POST /api/yclients/branches/sync
Analytics:      GET /api/analytics/{overview,usage}
Webhook:        POST /api/bot/incoming   (Gateway → Backend, internal)
```
