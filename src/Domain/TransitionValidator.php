<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

use GlavPro\CrmStages\DTO\ValidationResult;

/**
 * Валидатор условий выхода из стадий воронки.
 *
 * Для каждой стадии определяет набор обязательных событий,
 * которые должны быть зафиксированы перед переходом на следующую стадию.
 */
final class TransitionValidator
{
    /** @var int Максимальное количество дней с момента проведения демо */
    private const DEMO_FRESHNESS_DAYS = 60;

    /**
     * Проверить условия выхода для текущей стадии.
     *
     * Делегирует проверку в соответствующий приватный метод
     * в зависимости от кода стадии.
     *
     * @param string $currentStage Код текущей стадии компании
     * @param array<array<string, mixed>> $events Массив событий с ключами 'type', 'payload', 'created_at'
     * @return ValidationResult Результат валидации: valid или invalid с массивом ошибок
     */
    public function validate(string $currentStage, array $events): ValidationResult
    {
        return match ($currentStage) {
            'Ice' => $this->validateIce($events),
            'Touched' => $this->validateTouched($events),
            'Aware' => $this->validateAware($events),
            'Interested' => $this->validateInterested($events),
            'demo_planned' => $this->validateDemoPlanned($events),
            'Demo_done' => $this->validateDemoDone($events),
            'Committed' => $this->validateCommitted($events),
            'Customer' => $this->validateCustomer($events),
            'Activated' => ValidationResult::invalid(['Стадия Activated является терминальной, переходы невозможны']),
            'Null' => ValidationResult::invalid(['Стадия Null является терминальной, переходы невозможны']),
            default => ValidationResult::invalid(["Неизвестная стадия: {$currentStage}"]),
        };
    }

    /**
     * Проверить условия выхода из стадии Ice.
     *
     * Требуется: разговор с ЛПР (lpr_conversation).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateIce(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'lpr_conversation')) {
            return ValidationResult::invalid(['Требуется разговор с ЛПР (lpr_conversation)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии Touched.
     *
     * Требуется: заполненная форма дискавери (discovery_filled).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateTouched(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'discovery_filled')) {
            return ValidationResult::invalid(['Требуется заполненная форма дискавери (discovery_filled)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии Aware.
     *
     * Требуется: запланированная демонстрация (demo_planned).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateAware(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'demo_planned')) {
            return ValidationResult::invalid(['Требуется запланированная демонстрация (demo_planned)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии Interested.
     *
     * Требуется: событие demo_planned с заполненным полем scheduled_at в payload.
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateInterested(array $events): ValidationResult
    {
        $demoEvent = $this->findEventOfType($events, 'demo_planned');
        if ($demoEvent === null) {
            return ValidationResult::invalid(['Требуется запланированная демонстрация с датой (demo_planned)']);
        }
        $payload = $this->getPayload($demoEvent);
        if (empty($payload['scheduled_at'])) {
            return ValidationResult::invalid(['Событие demo_planned должно содержать дату (scheduled_at)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии demo_planned.
     *
     * Требуется: проведённая демонстрация (demo_conducted).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateDemoPlanned(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'demo_conducted')) {
            return ValidationResult::invalid(['Требуется проведённая демонстрация (demo_conducted)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии Demo_done.
     *
     * Требуется: демо проведено не более 60 дней назад (demo_conducted)
     * и наличие счёта (invoice_created) или КП (kp_sent).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateDemoDone(array $events): ValidationResult
    {
        $errors = [];

        // Check demo freshness (< 60 days)
        $demoEvent = $this->findEventOfType($events, 'demo_conducted');
        if ($demoEvent !== null) {
            $demoDate = $this->getCreatedAt($demoEvent);
            if ($demoDate !== null) {
                $daysSinceDemo = (new \DateTimeImmutable())->diff($demoDate)->days;
                if ($daysSinceDemo > self::DEMO_FRESHNESS_DAYS) {
                    $errors[] = "Демо проведено более {$daysSinceDemo} дней назад (лимит: " . self::DEMO_FRESHNESS_DAYS . " дней). Требуется новое демо.";
                }
            }
        }

        // Check invoice or KP exists
        $hasInvoice = $this->hasEventOfType($events, 'invoice_created');
        $hasKp = $this->hasEventOfType($events, 'kp_sent');
        if (!$hasInvoice && !$hasKp) {
            $errors[] = 'Требуется счёт (invoice_created) или коммерческое предложение (kp_sent)';
        }

        return empty($errors) ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }

    /**
     * Проверить условия выхода из стадии Demo_done с явной датой «сейчас».
     *
     * Аналог validateDemoDone(), но принимает текущую дату как параметр
     * для детерминированного тестирования правила свежести демо.
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @param \DateTimeImmutable $now Текущая дата для расчёта свежести демо
     * @return ValidationResult Результат валидации
     */
    public function validateDemoDoneAt(array $events, \DateTimeImmutable $now): ValidationResult
    {
        $errors = [];

        $demoEvent = $this->findEventOfType($events, 'demo_conducted');
        if ($demoEvent !== null) {
            $demoDate = $this->getCreatedAt($demoEvent);
            if ($demoDate !== null) {
                $daysSinceDemo = $now->diff($demoDate)->days;
                if ($daysSinceDemo > self::DEMO_FRESHNESS_DAYS) {
                    $errors[] = "Демо проведено более {$daysSinceDemo} дней назад (лимит: " . self::DEMO_FRESHNESS_DAYS . " дней). Требуется новое демо.";
                }
            }
        }

        $hasInvoice = $this->hasEventOfType($events, 'invoice_created');
        $hasKp = $this->hasEventOfType($events, 'kp_sent');
        if (!$hasInvoice && !$hasKp) {
            $errors[] = 'Требуется счёт (invoice_created) или коммерческое предложение (kp_sent)';
        }

        return empty($errors) ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }

    /**
     * Проверить условия выхода из стадии Committed.
     *
     * Требуется: подтверждение оплаты (payment_received).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateCommitted(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'payment_received')) {
            return ValidationResult::invalid(['Требуется подтверждение оплаты (payment_received)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить условия выхода из стадии Customer.
     *
     * Требуется: выданное удостоверение (certificate_issued).
     *
     * @param array<array<string, mixed>> $events Массив событий компании
     * @return ValidationResult Результат валидации
     */
    private function validateCustomer(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'certificate_issued')) {
            return ValidationResult::invalid(['Требуется выданное удостоверение (certificate_issued)']);
        }
        return ValidationResult::valid();
    }

    /**
     * Проверить наличие события указанного типа в массиве.
     *
     * @param array<array<string, mixed>> $events Массив событий
     * @param string $type Тип события для поиска
     * @return bool true, если событие найдено
     */
    private function hasEventOfType(array $events, string $type): bool
    {
        return $this->findEventOfType($events, $type) !== null;
    }

    /**
     * Найти первое событие указанного типа в массиве.
     *
     * @param array<array<string, mixed>> $events Массив событий
     * @param string $type Тип события для поиска
     * @return array<string, mixed>|null Найденное событие или null
     */
    private function findEventOfType(array $events, string $type): ?array
    {
        foreach ($events as $event) {
            $eventType = is_array($event) ? ($event['type'] ?? $event['event_type'] ?? null) : null;
            if ($eventType === $type) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Извлечь payload из события.
     *
     * Поддерживает payload как массив и как JSON-строку.
     *
     * @param array<string, mixed> $event Событие
     * @return array<string, mixed> Декодированный payload
     */
    private function getPayload(array $event): array
    {
        $payload = $event['payload'] ?? [];
        if (is_string($payload)) {
            return json_decode($payload, true) ?? [];
        }
        return (array) $payload;
    }

    /**
     * Извлечь дату создания события.
     *
     * @param array<string, mixed> $event Событие
     * @return \DateTimeImmutable|null Дата создания или null при отсутствии/ошибке парсинга
     */
    private function getCreatedAt(array $event): ?\DateTimeImmutable
    {
        $createdAt = $event['created_at'] ?? null;
        if ($createdAt === null) {
            return null;
        }
        try {
            return new \DateTimeImmutable($createdAt);
        } catch (\Exception) {
            return null;
        }
    }
}
