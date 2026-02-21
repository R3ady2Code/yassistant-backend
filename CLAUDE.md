# CLAUDE.md — Backend (Laravel)

Это инструкция для AI-ассистента по работе с кодовой базой Backend.

## Обзор проекта

Мультитенантный Laravel 12 API с DDD-архитектурой. Сервис управляет AI-ботами для записи клиентов через YClients. Принимает сообщения из мессенджеров (через отдельный Node.js Gateway), обрабатывает их через OpenAI с function calling, управляет бронированиями, сценариями и диалогами.

## Стек

- PHP 8.3, Laravel 12, PostgreSQL 16, Redis 7
- OpenAI API (GPT-4o + Whisper), YClients API
- HashiCorp Vault (dev: FakeVault через Redis)
- Laravel Reverb (WebSocket), Laravel Queue
- Docker Compose для инфраструктуры

## Архитектура и структура

### Слои (порядок зависимостей)

```
Http → Domain ← Adapters
         ↑
      Contracts (интерфейсы в Domain, реализации в Adapters)
```

- **Domain/** — бизнес-логика. НЕ зависит от Http, Adapters, Laravel facades.
- **Http/** — контроллеры, requests, resources. Зависит от Domain.
- **Adapters/** — реализации контрактов из Domain (Vault, OpenAI, YClients, Gateway, S3).
- **Providers/AdapterServiceProvider.php** — все биндинги Contract → Adapter.

### Домены

| Домен | Назначение |
|---|---|
| `Identity` | Тенанты, авторизация, Vault-секреты, YClients-токен |
| `Channel` | Telegram/WhatsApp каналы, webhook-регистрация |
| `Conversation` | Диалоги, сообщения (все типы), режимы (ai/manual/escalated), файлы |
| `AI` | OpenAI-клиент, промпт-билдер, function registry/executor, BotSettings, FAQ |
| `Booking` | YClients API — услуги, слоты, мастера, записи |
| `Scenario` | Блок-схемы (default + custom), движок выполнения, ноды |
| `Billing` | Подписки, лимиты, usage-логи |

### Структура каждого домена

```
Domain/{Name}/
├── Actions/          # Бизнес-операции (public API домена)
├── Models/           # Eloquent-модели
├── DataObjects/      # Иммутабельные DTO (final readonly class)
├── Enums/            # PHP 8.1+ backed enums
├── Services/         # Внутренняя логика (не бизнес-действие)
├── Jobs/             # Очередные задачи
├── Events/           # Доменные события
├── Listeners/        # Подписчики событий
├── Contracts/        # Интерфейсы внешних сервисов
├── Exceptions/       # Доменные исключения
└── QueryBuilders/    # Кастомные Eloquent-билдеры
```

## Правила кодирования (СТРОГО СОБЛЮДАТЬ)

### Общие

- `declare(strict_types=1);` в КАЖДОМ файле
- Все зависимости — через constructor injection. НИКОГДА `app()`, `resolve()`, фасады в Domain
- Именование: PascalCase для классов, camelCase для методов/свойств

### Actions

- `final class`, extends `AbstractAction`
- Единственный public метод: `handle(...)`
- **НЕЛЬЗЯ** вызывать Action из другого Action
- Action — это бизнес-операция, вызывается из Http-контроллера или Listener/Job
- Если нужна общая логика между Actions — вынести в Service

```php
<?php

declare(strict_types=1);

namespace App\Domain\{Name}\Actions;

use App\Abstracts\AbstractAction;

final class DoSomethingAction extends AbstractAction
{
    public function __construct(
        private readonly SomeDependency $dep,
    ) {
        parent::__construct();
    }

    public function handle(SomeData $data): ResultType
    {
        // логика
    }
}
```

### DataObjects (DTO)

- `final readonly class`
- Только public свойства через constructor promotion
- Без логики, без зависимостей
- Допускается static-метод `fromArray()` или `fromRequest()`

```php
<?php

declare(strict_types=1);

namespace App\Domain\{Name}\DataObjects;

final readonly class SomeData
{
    public function __construct(
        public string $name,
        public int $value,
        public ?string $optional = null,
    ) {}
}
```

### Models

- Eloquent-модели живут в `Domain/{Name}/Models/`
- Модели НЕ обращаются к внешним сервисам (Vault, API) — это делают Actions/Adapters
- Обязательно: `$casts` для enums, json, dates
- Связи (`hasMany`, `belongsTo`) — допустимы

### Adapters

- `readonly class`, реализует контракт (interface) из Domain
- Контракт живёт в `Domain/{Name}/Contracts/`
- Адаптер живёт в `Adapters/{ServiceName}/`
- Биндинг в `Providers/AdapterServiceProvider.php`

### Http

- Контроллеры — тонкие. Валидация через FormRequest, вызов Action, возврат Resource.
- Группировка по фичам: `Http/Channels/`, `Http/Conversations/`, и т.д.
- Каждая фича содержит: Controller, Request(s), Resource(s)

```php
final class SomeController extends AbstractController
{
    public function __invoke(
        SomeRequest $request,
        SomeAction $action,
    ): JsonResponse {
        $data = new SomeData(...$request->validated());
        $result = $action->handle($data);
        return response()->json(new SomeResource($result));
    }
}
```

### Enums

- PHP 8.1+ backed enums (`string` или `int`)
- Живут в `Domain/{Name}/Enums/`

### Тесты

- Unit-тесты для Actions: `tests/Unit/Domain/{Name}/Actions/`
- Feature-тесты для HTTP: `tests/Feature/Http/{Feature}/`
- Используй `RefreshDatabase` trait
- Фабрики для моделей в `database/factories/`

## Ключевые потоки

### Входящее сообщение (любой тип)

```
Gateway POST /api/bot/incoming
  → BotWebhookController
    → IncomingMessageRequest (валидация: channel_id, type, text?, attachments?)
    → ProcessIncomingMessageAction.handle(IncomingMessageData)
      1. findOrCreateConversation
      2. Сохранить Message (type + attachments в jsonb)
      3. Если есть file_id → dispatch DownloadTelegramFileJob
      4. Если voice → dispatch TranscribeVoiceMessageJob
      5. Проверить conversation.mode:
         - AI: MessageTextPreparer → GenerateAIResponseAction → ответ
         - Manual/Escalated: event(MessageReceived) → WebSocket → нет ответа
```

### AI с function calling

```
GenerateAIResponseAction:
  1. PromptBuilder.build() → system prompt
  2. ConversationContextLoader.load() → последние N сообщений
  3. FunctionRegistry.forTenant() → доступные functions
  4. Цикл (макс 5 итераций):
     - OpenAI chat completion
     - Если текст → сохранить, вернуть
     - Если function_call → FunctionExecutor → результат → добавить в контекст → repeat
     - Если escalate_to_human → вернуть эскалацию
```

### Типы сообщений (Message.type)

```
text, photo, voice, video, document, location, contact, sticker, callback_query
```

Каждый тип имеет свой формат `attachments` (jsonb). MessageTextPreparer конвертирует любой тип в текстовое описание для AI.

### Vault — хранение секретов

- В PostgreSQL хранится только `vault_path` (строка-ссылка)
- Реальные токены — в Vault (prod) или зашифрованы в Redis (dev, FakeVault)
- Пути: `tenants/{id}/yclients_api_token`, `tenants/{id}/channels/{id}/bot_token`

### Сценарии

- Два дефолтных: FAQ (`slug: faq`), YClients Pipeline (`slug: yclients_pipeline`)
- Дефолтные нельзя удалить, можно только toggle
- Кастомные — полный CRUD + визуальный редактор (React Flow на фронте)
- Schema хранится в jsonb: `{nodes: [...], edges: [...], start_node: "..."}`
- ScenarioEngine выполняет пошагово, состояние в `conversation.scenario_state`

## Частые задачи

### Добавить новый домен

```bash
mkdir -p app/Domain/NewDomain/{Actions,Models,DataObjects,Enums}
# Создать модель, миграцию, Actions, DTO
# Если есть внешний сервис → добавить Contracts/ + Adapter + биндинг в AdapterServiceProvider
```

### Добавить новую OpenAI function

1. Добавить case в `AIFunction` enum (`Domain/AI/Enums/`)
2. Добавить Action в соответствующий домен
3. Зарегистрировать в `FunctionExecutor` (match case)
4. Добавить definition в `FunctionRegistry`
5. Добавить в `allowed_operations` в BotSettings (если нужна настройка per-tenant)

### Добавить новый тип сообщения

1. Добавить case в `MessageType` enum
2. Обновить `MessageTextPreparer.prepare()` — текст для AI
3. Обновить Gateway `telegram-parser.ts` — парсинг из Telegram update
4. Обновить `MessageResource` если нужны доп.поля
5. Обновить фронтенд `MessageContent.tsx` — рендеринг

### Добавить внешний сервис

1. Создать контракт (interface) в `Domain/{Name}/Contracts/`
2. Создать адаптер в `Adapters/{Service}/`
3. Зарегистрировать биндинг в `AdapterServiceProvider`
4. Инжектить контракт через конструктор в Actions

## Команды для разработки

```bash
# Миграции
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed

# Тесты
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=ProcessIncomingMessageActionTest

# Код-стайл
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse

# Очереди (мониторинг)
docker compose exec app php artisan queue:work --tries=3
docker compose logs -f queue-worker

# Роуты
docker compose exec app php artisan route:list --path=api

# Tinker
docker compose exec app php artisan tinker
```

## Что НЕ делать

- Не вызывать Action из Action — используй Service или Event/Listener
- Не использовать фасады (`Cache::`, `Http::`) в Domain — инжекти через контракт
- Не хранить секреты (токены, ключи) в PostgreSQL — только vault_path
- Не писать бизнес-логику в контроллерах — только валидация + вызов Action + Resource
- Не использовать `app()`, `resolve()` — только constructor injection
- Не создавать God-классы — один Action = одна операция
- Не забывать `declare(strict_types=1);`
