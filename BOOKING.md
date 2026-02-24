# Запись: дефолтный алгоритм + кастомные flow

## Два пути записи

### Дефолтный алгоритм (не меняется)

Стандартный AI flow через function calling. AI сам ведёт диалог:
```
Клиент хочет записаться
  → AI спрашивает какую услугу (get_services)
  → AI предлагает мастера (get_staff)
  → AI подбирает дату/время (get_available_slots)
  → AI собирает имя/телефон
  → Подтверждение → Запись (create_booking)
```

Работает для ВСЕХ услуг, у которых нет кастомного flow.

### Кастомный flow (новое)

Тенант создаёт flow для **конкретной услуги**. Услуга зашита — бот НЕ спрашивает "какую услугу хотите". Бот задаёт только заданные вопросы, потом стандартные шаги (дата, контакты).

```
Клиент хочет записаться на "Баню"
  → Система видит: для "Баня" есть кастомный flow
  → Бот: "Сколько человек?" → ответ: 2
  → Бот: "На какое время?" → ответ: 90 минут
  → Бот: "Нужны веники?" → ответ: да
  → Бот подбирает дату/время (get_available_slots)
  → Бот собирает имя/телефон
  → Подтверждение → Запись
```

### Как выбирается путь

```
Клиент пишет → AI определяет intent "запись" + услугу
                      │
            ┌─────────┴──────────┐
            ▼                    ▼
     Услуга понятна         Услуга неясна
            │                    │
            ▼                    ▼
   Есть кастомный flow?    Дефолтный алгоритм
      │           │         (AI сам разберётся)
     Да          Нет
      │           │
      ▼           ▼
  Кастомный    Дефолтный
    flow       алгоритм
```

---

## Модель данных

### booking_flows

Один flow = одна услуга. У тенанта может быть 0..N flow для разных услуг.

```sql
CREATE TABLE booking_flows (
    id              bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    tenant_id       uuid NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name            varchar(255) NOT NULL,          -- "Запись на баню", для админки
    yclients_service_id  integer NOT NULL,           -- ID услуги в YClients
    yclients_service_name varchar(255) NOT NULL,     -- название для отображения
    yclients_branch_id   integer NOT NULL,           -- филиал
    ask_staff       boolean DEFAULT false,           -- спрашивать мастера?
    is_active       boolean DEFAULT true,
    created_at      timestamp DEFAULT now(),
    updated_at      timestamp DEFAULT now(),

    UNIQUE (tenant_id, yclients_service_id)          -- одна услуга = один flow
);
```

### booking_flow_steps

Вопросы в flow. Строго линейный порядок.

```sql
CREATE TABLE booking_flow_steps (
    id              bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    flow_id         bigint NOT NULL REFERENCES booking_flows(id) ON DELETE CASCADE,
    question_text   varchar(500) NOT NULL,           -- "Сколько человек будет?"
    answer_type     varchar(20) NOT NULL,            -- number | choice | text | yes_no
    is_required     boolean DEFAULT true,
    config          jsonb DEFAULT '{}',              -- настройки по типу
    sort_order      integer DEFAULT 0,
    created_at      timestamp DEFAULT now(),
    updated_at      timestamp DEFAULT now()
);

CREATE INDEX idx_flow_steps_order ON booking_flow_steps(flow_id, sort_order);
```

### Типы ответов — только 4 (убрал date, она не нужна как кастомный вопрос)

| answer_type | Что вводит клиент | config | Пример |
|---|---|---|---|
| `number` | Целое число | `{"min": 1, "max": 10}` | "Сколько человек?" → 2 |
| `choice` | Один из вариантов | `{"options": ["30 мин", "60 мин", "90 мин"]}` | "Длительность?" → 90 мин |
| `text` | Свободный текст | `{"max_length": 300}` | "Пожелания?" → "Без эвкалипта" |
| `yes_no` | Да или нет | `{}` | "Нужны веники?" → Да |

Дата/время и контакты — это НЕ кастомные вопросы, они всегда задаются автоматически после всех кастомных шагов.

### conversations — pipeline state

```sql
-- Заменяем active_scenario_id + scenario_state на:
booking_flow_id  bigint REFERENCES booking_flows(id) NULLABLE,
pipeline_state   jsonb NULLABLE
```

Формат `pipeline_state`:
```jsonc
{
  "phase": "custom_questions",       // текущая фаза
  "current_step_id": 5,             // ID текущего вопроса (или null)
  "answers": {                       // ответы на кастомные вопросы
    "3": {"question": "Сколько человек?", "value": 2, "raw": "нас двое"},
    "5": {"question": "Длительность?", "value": "90 мин", "raw": "полтора часа"},
    "8": {"question": "Нужны веники?", "value": true, "raw": "да"}
  },
  // Стандартные данные (заполняются на соответствующих фазах):
  "staff_id": null,
  "staff_name": null,
  "date": "2026-03-18",
  "time": "14:00",
  "client_name": "Иван",
  "client_phone": "+79001234567"
}
```

---

## Фазы кастомного flow

Строго линейные, всегда в этом порядке:

```
┌──────────────────────┐
│ 1. custom_questions   │  Кастомные вопросы тенанта (по одному)
│    (0..N шагов)       │  Если вопросов нет — пропускается
└──────────┬───────────┘
           ▼
┌──────────────────────┐
│ 2. select_staff       │  Только если ask_staff = true
│    (опционально)      │
└──────────┬───────────┘
           ▼
┌──────────────────────┐
│ 3. select_datetime    │  Всегда. AI + get_available_slots
└──────────┬───────────┘
           ▼
┌──────────────────────┐
│ 4. collect_contacts   │  Всегда. Имя + телефон
└──────────┬───────────┘
           ▼
┌──────────────────────┐
│ 5. confirm            │  Всегда. Показать summary, ждать "да"
└──────────┬───────────┘
           ▼
┌──────────────────────┐
│ 6. complete           │  Создание записи в YClients
└──────────────────────┘
```

---

## Доменная модель

### Что убираем

```
УДАЛИТЬ полностью:
├── Domain/Scenario/           -- весь домен
├── Adapters/Scenario/
├── Http/Scenarios/
├── frontend: ScenariosListPage, ScenarioEditorPage, React Flow, все ноды
└── таблица scenarios
```

### Что добавляем

```
Domain/Booking/
├── Models/
│   ├── ... (существующие)
│   ├── BookingFlow.php                  -- НОВЫЙ
│   └── BookingFlowStep.php             -- НОВЫЙ
├── Actions/
│   ├── ... (существующие: GetServices, CreateBooking, и т.д.)
│   ├── CreateBookingFlowAction.php     -- НОВЫЙ
│   ├── UpdateBookingFlowAction.php     -- НОВЫЙ
│   ├── DeleteBookingFlowAction.php     -- НОВЫЙ
│   ├── ReorderFlowStepsAction.php      -- НОВЫЙ
│   └── FindFlowForServiceAction.php    -- НОВЫЙ: поиск flow по service_id
├── DataObjects/
│   ├── ... (существующие)
│   ├── BookingFlowData.php             -- НОВЫЙ
│   └── FlowStepData.php               -- НОВЫЙ
├── Enums/
│   ├── ... (существующие)
│   └── AnswerType.php                  -- НОВЫЙ: number, choice, text, yes_no

Domain/Conversation/
├── Services/
│   ├── ... (существующие)
│   └── BookingPipelineManager.php      -- НОВЫЙ
├── Enums/
│   ├── ... (существующие)
│   └── PipelinePhase.php               -- НОВЫЙ

Http/
├── BookingFlows/                        -- НОВЫЙ
│   ├── BookingFlowController.php
│   ├── CreateBookingFlowRequest.php
│   ├── UpdateBookingFlowRequest.php
│   ├── BookingFlowResource.php
│   └── FlowStepResource.php
```

---

## Ключевые классы

### Enums

```php
<?php
declare(strict_types=1);
namespace App\Domain\Booking\Enums;

enum AnswerType: string
{
    case Number = 'number';
    case Choice = 'choice';
    case Text = 'text';
    case YesNo = 'yes_no';
}
```

```php
<?php
declare(strict_types=1);
namespace App\Domain\Conversation\Enums;

enum PipelinePhase: string
{
    case CustomQuestions = 'custom_questions';
    case SelectStaff = 'select_staff';
    case SelectDatetime = 'select_datetime';
    case CollectContacts = 'collect_contacts';
    case Confirm = 'confirm';
    case Complete = 'complete';
}
```

### Models

```php
<?php
declare(strict_types=1);
namespace App\Domain\Booking\Models;

class BookingFlow extends Model
{
    protected $casts = [
        'ask_staff' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(BookingFlowStep::class, 'flow_id')
            ->orderBy('sort_order');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

```php
<?php
declare(strict_types=1);
namespace App\Domain\Booking\Models;

class BookingFlowStep extends Model
{
    protected $casts = [
        'answer_type' => AnswerType::class,
        'is_required' => 'boolean',
        'config' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(BookingFlow::class, 'flow_id');
    }

    /**
     * Человекочитаемое описание ожидаемого ответа (для AI prompt)
     */
    public function describeExpectedAnswer(): string
    {
        return match ($this->answer_type) {
            AnswerType::Number => sprintf(
                'целое число от %d до %d',
                $this->config['min'] ?? 1,
                $this->config['max'] ?? 100,
            ),
            AnswerType::Choice => sprintf(
                'один из вариантов: %s',
                implode(', ', $this->config['options'] ?? []),
            ),
            AnswerType::Text => 'текстовый ответ',
            AnswerType::YesNo => 'да или нет',
        };
    }
}
```

### BookingPipelineManager

```php
<?php
declare(strict_types=1);
namespace App\Domain\Conversation\Services;

use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Booking\Models\BookingFlowStep;
use App\Domain\Conversation\Enums\PipelinePhase;
use App\Domain\Conversation\Models\Conversation;

final class BookingPipelineManager
{
    /**
     * Запустить кастомный flow для конкретной услуги
     */
    public function startFlow(Conversation $conversation, BookingFlow $flow): void
    {
        $firstStep = $flow->steps()->first();

        $conversation->update([
            'booking_flow_id' => $flow->id,
            'pipeline_state' => [
                'phase' => $firstStep
                    ? PipelinePhase::CustomQuestions->value
                    : ($flow->ask_staff
                        ? PipelinePhase::SelectStaff->value
                        : PipelinePhase::SelectDatetime->value),
                'current_step_id' => $firstStep?->id,
                'answers' => [],
            ],
        ]);
    }

    /**
     * Текущая фаза
     */
    public function currentPhase(Conversation $conversation): ?PipelinePhase
    {
        return $conversation->pipeline_state
            ? PipelinePhase::from($conversation->pipeline_state['phase'])
            : null;
    }

    /**
     * Текущий кастомный вопрос (или null если фаза не custom_questions)
     */
    public function currentStep(Conversation $conversation): ?BookingFlowStep
    {
        $stepId = $conversation->pipeline_state['current_step_id'] ?? null;
        return $stepId ? BookingFlowStep::find($stepId) : null;
    }

    /**
     * Сохранить ответ на кастомный вопрос и перейти дальше
     */
    public function saveAnswerAndAdvance(
        Conversation $conversation,
        BookingFlowStep $step,
        mixed $value,
        string $rawInput,
    ): PipelinePhase {
        $state = $conversation->pipeline_state;

        // Сохраняем ответ
        $state['answers'][(string) $step->id] = [
            'question' => $step->question_text,
            'value' => $value,
            'raw' => $rawInput,
        ];

        // Ищем следующий вопрос
        $nextStep = BookingFlowStep::query()
            ->where('flow_id', $conversation->booking_flow_id)
            ->where('sort_order', '>', $step->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextStep) {
            // Ещё есть вопросы
            $state['phase'] = PipelinePhase::CustomQuestions->value;
            $state['current_step_id'] = $nextStep->id;
        } else {
            // Вопросы кончились → следующая фаза
            $state['current_step_id'] = null;
            $flow = BookingFlow::find($conversation->booking_flow_id);
            $state['phase'] = $flow->ask_staff
                ? PipelinePhase::SelectStaff->value
                : PipelinePhase::SelectDatetime->value;
        }

        $conversation->update(['pipeline_state' => $state]);

        return PipelinePhase::from($state['phase']);
    }

    /**
     * Сохранить данные стандартной фазы и перейти дальше
     */
    public function savePhaseDataAndAdvance(
        Conversation $conversation,
        array $data,
    ): PipelinePhase {
        $state = $conversation->pipeline_state;
        $state = array_merge($state, $data);

        $current = PipelinePhase::from($state['phase']);
        $next = match ($current) {
            PipelinePhase::SelectStaff => PipelinePhase::SelectDatetime,
            PipelinePhase::SelectDatetime => PipelinePhase::CollectContacts,
            PipelinePhase::CollectContacts => PipelinePhase::Confirm,
            PipelinePhase::Confirm => PipelinePhase::Complete,
            default => $current,
        };

        $state['phase'] = $next->value;
        $conversation->update(['pipeline_state' => $state]);

        return $next;
    }

    /**
     * Собрать summary для подтверждения
     */
    public function buildSummary(Conversation $conversation): string
    {
        $state = $conversation->pipeline_state;
        $flow = BookingFlow::find($conversation->booking_flow_id);

        $lines = ["Услуга: {$flow->yclients_service_name}"];

        if ($state['staff_name'] ?? null) {
            $lines[] = "Мастер: {$state['staff_name']}";
        }

        // Кастомные ответы
        foreach ($state['answers'] as $a) {
            $displayValue = is_bool($a['value'])
                ? ($a['value'] ? 'Да' : 'Нет')
                : $a['value'];
            $lines[] = "{$a['question']}: {$displayValue}";
        }

        $lines[] = "Дата: {$state['date']}";
        $lines[] = "Время: {$state['time']}";
        $lines[] = "Имя: {$state['client_name']}";
        $lines[] = "Телефон: {$state['client_phone']}";

        return implode("\n", $lines);
    }

    /**
     * Собрать комментарий для YClients из кастомных ответов
     */
    public function buildYClientsComment(Conversation $conversation): string
    {
        $state = $conversation->pipeline_state;
        $parts = [];

        foreach ($state['answers'] as $a) {
            $displayValue = is_bool($a['value'])
                ? ($a['value'] ? 'Да' : 'Нет')
                : $a['value'];
            $parts[] = "{$a['question']}: {$displayValue}";
        }

        return implode('; ', $parts);
    }

    /**
     * Сброс pipeline
     */
    public function reset(Conversation $conversation): void
    {
        $conversation->update([
            'booking_flow_id' => null,
            'pipeline_state' => null,
        ]);
    }

    /**
     * Pipeline активен?
     */
    public function isActive(Conversation $conversation): bool
    {
        return $conversation->booking_flow_id !== null
            && $conversation->pipeline_state !== null;
    }
}
```

### Валидация ответов

```php
<?php
declare(strict_types=1);
namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Enums\AnswerType;
use App\Domain\Booking\Models\BookingFlowStep;

final class ValidateFlowStepAnswerAction extends AbstractAction
{
    /**
     * Валидирует извлечённое AI значение.
     * Возвращает нормализованное значение или null если невалидно.
     */
    public function handle(BookingFlowStep $step, string $extracted): int|string|bool|null
    {
        return match ($step->answer_type) {
            AnswerType::Number => $this->validateNumber($step, $extracted),
            AnswerType::Choice => $this->validateChoice($step, $extracted),
            AnswerType::Text   => $this->validateText($step, $extracted),
            AnswerType::YesNo  => $this->validateYesNo($extracted),
        };
    }

    private function validateNumber(BookingFlowStep $step, string $raw): ?int
    {
        if (!is_numeric($raw)) return null;

        $value = (int) $raw;
        $min = $step->config['min'] ?? 1;
        $max = $step->config['max'] ?? 100;

        return ($value >= $min && $value <= $max) ? $value : null;
    }

    private function validateChoice(BookingFlowStep $step, string $raw): ?string
    {
        $options = $step->config['options'] ?? [];
        // AI нормализовал — проверяем точное совпадение
        return in_array($raw, $options, true) ? $raw : null;
    }

    private function validateText(BookingFlowStep $step, string $raw): ?string
    {
        $maxLength = $step->config['max_length'] ?? 500;
        return mb_strlen($raw) <= $maxLength ? $raw : null;
    }

    private function validateYesNo(string $raw): ?bool
    {
        return match ($raw) {
            'yes', 'true', '1' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }
}
```

---

## Интеграция с AI

### Как AI узнаёт про кастомный flow

В system prompt добавляется блок, когда pipeline активен:

```
--- РЕЖИМ ЗАПИСИ ---
Клиент записывается на услугу: "Баня"
Услуга уже выбрана, НЕ спрашивай какую услугу хочет клиент.

Текущий этап: кастомный вопрос
Вопрос: "Сколько человек будет?"
Ожидаемый ответ: целое число от 1 до 10

Из ответа клиента извлеки число и вызови save_custom_answer.
Если ответ непонятен — переспроси дружелюбно.
Если клиент хочет отменить запись — вызови cancel_pipeline.
```

Для стандартных фаз:
```
--- РЕЖИМ ЗАПИСИ ---
Услуга: Баня
Собранные данные:
- Сколько человек: 2
- Длительность: 90 мин
- Нужны веники: Да

Текущий этап: выбор даты и времени
Используй функцию get_available_slots чтобы показать свободные слоты.
```

### Functions для кастомного flow

Не нужны отдельные — переиспользуем существующие + добавляем 3:

```json
[
  {
    "name": "start_custom_flow",
    "description": "Начать запись по кастомному flow. Вызывай когда определил что клиент хочет услугу, для которой есть кастомный flow.",
    "parameters": {
      "type": "object",
      "properties": {
        "service_id": {"type": "integer", "description": "ID услуги в YClients"}
      },
      "required": ["service_id"]
    }
  },
  {
    "name": "save_custom_answer",
    "description": "Сохранить ответ на текущий кастомный вопрос.",
    "parameters": {
      "type": "object",
      "properties": {
        "step_id": {"type": "integer"},
        "extracted_value": {"type": "string", "description": "Извлечённое значение. Для number — число строкой. Для choice — точный вариант из списка. Для yes_no — 'yes' или 'no'. Для text — текст."}
      },
      "required": ["step_id", "extracted_value"]
    }
  },
  {
    "name": "cancel_pipeline",
    "description": "Клиент передумал записываться. Отменить текущий flow.",
    "parameters": {"type": "object", "properties": {}}
  }
]
```

Остальные функции (`get_available_slots`, `get_staff`, `create_booking`, `escalate_to_human`) — без изменений.

### Как AI определяет что нужен кастомный flow

AI получает в system prompt список услуг с кастомными flow:

```
Для следующих услуг настроена автоматическая запись:
- "Баня" (service_id: 15) — вызови start_custom_flow
- "VIP-массаж" (service_id: 23) — вызови start_custom_flow

Для остальных услуг используй стандартный flow (get_services, get_available_slots, create_booking).
```

---

## ProcessIncomingMessageAction — изменения

```php
private function handleAIMode(
    Conversation $conversation,
    IncomingMessageData $data,
): OutgoingMessageData {
    $textForAI = $this->textPreparer->prepare($data);

    // 1. Активен кастомный pipeline?
    if ($this->pipelineManager->isActive($conversation)) {
        // AI получает контекст текущей фазы pipeline
        // и продолжает сбор данных
        $result = $this->generateAIResponse->handle($conversation, $textForAI);
        // ... обработка эскалации ...
        return $result->toOutgoingMessage();
    }

    // 2. Свободный AI-диалог (может запустить start_custom_flow
    //    или использовать дефолтный flow)
    $result = $this->generateAIResponse->handle($conversation, $textForAI);
    // ... обработка эскалации ...
    return $result->toOutgoingMessage();
}
```

Вся магия — в том что `GenerateAIResponseAction` собирает разный system prompt и разные functions в зависимости от наличия активного pipeline.

### FunctionExecutor — новые функции

```php
// Добавить в match:
'start_custom_flow' => $this->startCustomFlow($tenant, $conversation, $arguments),
'save_custom_answer' => $this->saveCustomAnswer($conversation, $arguments),
'cancel_pipeline'   => $this->cancelPipeline($conversation),
```

```php
private function startCustomFlow(
    Tenant $tenant,
    Conversation $conversation,
    array $args,
): array {
    $flow = BookingFlow::query()
        ->where('tenant_id', $tenant->id)
        ->where('yclients_service_id', $args['service_id'])
        ->where('is_active', true)
        ->first();

    if (!$flow) {
        return ['error' => 'Кастомный flow не найден для этой услуги'];
    }

    $this->pipelineManager->startFlow($conversation, $flow);

    $firstStep = $flow->steps()->first();

    return [
        'status' => 'started',
        'service_name' => $flow->yclients_service_name,
        'first_question' => $firstStep ? [
            'step_id' => $firstStep->id,
            'question' => $firstStep->question_text,
            'expected' => $firstStep->describeExpectedAnswer(),
        ] : null,
        'phase' => $this->pipelineManager->currentPhase($conversation)->value,
    ];
}

private function saveCustomAnswer(
    Conversation $conversation,
    array $args,
): array {
    $step = BookingFlowStep::findOrFail($args['step_id']);

    // Валидация
    $validated = $this->validateAnswer->handle($step, $args['extracted_value']);

    if ($validated === null) {
        return [
            'status' => 'invalid',
            'message' => "Ответ не подходит. Ожидается: {$step->describeExpectedAnswer()}",
        ];
    }

    // Сохраняем и переходим дальше
    $nextPhase = $this->pipelineManager->saveAnswerAndAdvance(
        $conversation, $step, $validated, $args['extracted_value'],
    );

    $result = [
        'status' => 'saved',
        'phase' => $nextPhase->value,
    ];

    // Если следующий шаг — тоже кастомный вопрос
    if ($nextPhase === PipelinePhase::CustomQuestions) {
        $nextStep = $this->pipelineManager->currentStep($conversation);
        $result['next_question'] = [
            'step_id' => $nextStep->id,
            'question' => $nextStep->question_text,
            'expected' => $nextStep->describeExpectedAnswer(),
        ];
    }

    return $result;
}

private function cancelPipeline(Conversation $conversation): array
{
    $this->pipelineManager->reset($conversation);
    return ['status' => 'cancelled'];
}
```

---

## Пример полного диалога

```
Клиент: Привет, хочу в баню записаться

AI: (видит в промпте: "Баня" service_id=15 — есть кастомный flow)
AI: (вызывает start_custom_flow: service_id=15)
AI: (получает first_question: "Сколько человек?", expected: "число от 1 до 10")

AI: Привет! Отлично, давайте запишу вас в баню 🧖
    Сколько человек будет?

Клиент: нас будет четверо

AI: (извлекает "4" из "четверо")
AI: (вызывает save_custom_answer: step_id=3, extracted_value="4")
AI: (получает next_question: "На какое время?", expected: "30 мин, 60 мин, 90 мин, 120 мин")

AI: Четверо, записал! На какое время хотите — 30, 60, 90 или 120 минут?

Клиент: ну часа полтора наверное

AI: (извлекает "90 мин")
AI: (вызывает save_custom_answer: step_id=5, extracted_value="90 мин")
AI: (получает next_question: "Нужны веники?", expected: "да или нет")

AI: 90 минут, отлично! Нужны веники?

Клиент: давайте

AI: (извлекает "yes")
AI: (вызывает save_custom_answer: step_id=8, extracted_value="yes")
AI: (получает phase: "select_datetime" — кастомные вопросы кончились)
AI: (вызывает get_available_slots: service_id=15, branch_id=1, date=завтра)

AI: С вениками! На какую дату вам удобно? Ближайшее свободное:
    — Завтра: 16:00, 18:00
    — Среда: 10:00, 14:00, 16:00

Клиент: завтра на 18

AI: (save_phase: date=2026-03-25, time=18:00)
AI: Как вас зовут и телефон для записи?

Клиент: Иван, 8 900 123 45 67

AI: (save_phase: client_name=Иван, client_phone=+79001234567)
AI: Подтвердите запись:
    Услуга: Баня
    Человек: 4
    Длительность: 90 мин
    Веники: Да
    Дата: 25 марта в 18:00
    Имя: Иван
    Телефон: +7 900 123 45 67
    Всё верно?

Клиент: да

AI: (вызывает create_booking с комментарием "Человек: 4; Длительность: 90 мин; Веники: Да")
AI: Готово, вы записаны! Ждём вас завтра в 18:00 🎉
```

---

## Админка — UI

### BookingFlowsPage (вместо ScenariosListPage)

```
┌────────────────────────────────────────────────────────┐
│  Записи на услуги                        [+ Создать]   │
│                                                         │
│  Для услуг без кастомного flow бот использует            │
│  стандартный алгоритм (AI сам подбирает услугу).        │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │ 🟢 Баня                              [✏️] [🗑]   │  │
│  │    Филиал: Центральный                             │  │
│  │    3 вопроса · Мастер: не спрашивать               │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │ 🟢 VIP-массаж                        [✏️] [🗑]   │  │
│  │    Филиал: Центральный                             │  │
│  │    1 вопрос · Мастер: спрашивать                   │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ⚪ Стрижка (выкл)                     [✏️] [🗑]   │  │
│  │    Филиал: Центральный                             │  │
│  │    0 вопросов · Мастер: спрашивать                 │  │
│  └───────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────┘
```

### BookingFlowEditPage

```
┌────────────────────────────────────────────────────────┐
│  ← Назад          Редактировать flow                    │
│                                                         │
│  Название: [Запись на баню                          ]   │
│  Услуга:   [▾ Баня — 2000₽, 60 мин                ]   │
│  Филиал:   [▾ Центральный                          ]   │
│                                                         │
│  [✓] Активен                                            │
│  [ ] Спрашивать мастера                                 │
│                                                         │
│  ── Вопросы перед записью ──────────────────────────    │
│                                                         │
│  ☰ 1. Сколько человек?                                  │
│       Тип: число (от 1 до 10) · обязательный    [✏️][🗑]│
│                                                         │
│  ☰ 2. На какое время?                                   │
│       Тип: выбор (30 мин, 60 мин, 90 мин, 120 мин)     │
│       обязательный                               [✏️][🗑]│
│                                                         │
│  ☰ 3. Нужны веники?                                     │
│       Тип: да/нет · необязательный               [✏️][🗑]│
│                                                         │
│  [+ Добавить вопрос]                                    │
│                                                         │
│  ── После вопросов бот автоматически: ──────────────    │
│  ✓ Подберёт дату и время                                │
│  ✓ Спросит имя и телефон                                │
│  ✓ Покажет подтверждение                                │
│  ✓ Создаст запись в YClients                            │
│                                                         │
│                              [Отмена]  [Сохранить]      │
└────────────────────────────────────────────────────────┘
```

### Модалка добавления вопроса

```
┌──────────────────────────────────────────┐
│  Добавить вопрос                          │
│                                           │
│  Текст: [Сколько человек будет?       ]   │
│                                           │
│  Тип:   (•) Число                         │
│         ( ) Выбор из вариантов            │
│         ( ) Свободный текст               │
│         ( ) Да / Нет                      │
│                                           │
│  ── Настройки ──                          │
│  Мин: [1]    Макс: [10]                   │
│                                           │
│  [✓] Обязательный                         │
│                                           │
│            [Отмена]  [Добавить]            │
└──────────────────────────────────────────┘
```

---

## API Endpoints

### Убираем
```
/api/scenarios/*           -- всё удаляем
```

### Добавляем
```
GET    /api/booking-flows                  -- список flow тенанта
POST   /api/booking-flows                  -- создать flow
GET    /api/booking-flows/{id}             -- flow с steps
PUT    /api/booking-flows/{id}             -- обновить flow (включая steps)
DELETE /api/booking-flows/{id}             -- удалить flow
PUT    /api/booking-flows/{id}/reorder     -- порядок вопросов
POST   /api/booking-flows/{id}/toggle      -- вкл/выкл
```

### Обновляем bot/settings
```
PUT    /api/bot/settings
-- + booking_pipeline_enabled: boolean (глобальный вкл/выкл кастомных flow)
-- + staff_selection_enabled: boolean (дефолтный алгоритм: спрашивать мастера)
```

---

## Влияние на этапы

### Этап 6: было 3 недели → стало 1 неделя

**Backend (3-4 дня):**
- [ ] BookingFlow, BookingFlowStep models
- [ ] AnswerType enum, PipelinePhase enum
- [ ] CRUD Actions для booking flows
- [ ] ValidateFlowStepAnswerAction
- [ ] BookingPipelineManager
- [ ] Интеграция в ProcessIncomingMessageAction
- [ ] Новые functions: start_custom_flow, save_custom_answer, cancel_pipeline
- [ ] Обновить PromptBuilder (контекст pipeline + список услуг с flow)

**Frontend (3-4 дня):**
- [ ] BookingFlowsPage (список)
- [ ] BookingFlowEditPage (форма + inline вопросы)
- [ ] QuestionFormModal (создание/редактирование вопроса)
- [ ] Drag-n-drop reorder вопросов
- [ ] Обновить BotSettingsPage

### Итого экономия

| | Старый план | Новый план |
|---|---|---|
| Этап 6 | 3 недели (ScenarioEngine, React Flow, 7 типов нод) | 1 неделя (CRUD + простой UI) |
| MVP (один) | ~20 нед | ~18 нед |
| MVP (двое) | ~13 нед | ~12 нед |
