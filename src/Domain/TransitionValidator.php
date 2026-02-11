<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

use GlavPro\CrmStages\DTO\ValidationResult;

final class TransitionValidator
{
    private const DEMO_FRESHNESS_DAYS = 60;

    /**
     * Validate exit conditions for the current stage.
     *
     * @param string $currentStage
     * @param array  $events Array of Event-like arrays with 'type', 'payload', 'created_at' keys
     * @return ValidationResult
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

    private function validateIce(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'lpr_conversation')) {
            return ValidationResult::invalid(['Требуется разговор с ЛПР (lpr_conversation)']);
        }
        return ValidationResult::valid();
    }

    private function validateTouched(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'discovery_filled')) {
            return ValidationResult::invalid(['Требуется заполненная форма дискавери (discovery_filled)']);
        }
        return ValidationResult::valid();
    }

    private function validateAware(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'demo_planned')) {
            return ValidationResult::invalid(['Требуется запланированная демонстрация (demo_planned)']);
        }
        return ValidationResult::valid();
    }

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

    private function validateDemoPlanned(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'demo_conducted')) {
            return ValidationResult::invalid(['Требуется проведённая демонстрация (demo_conducted)']);
        }
        return ValidationResult::valid();
    }

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
     * Validate Demo_done with explicit "now" for testability.
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

    private function validateCommitted(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'payment_received')) {
            return ValidationResult::invalid(['Требуется подтверждение оплаты (payment_received)']);
        }
        return ValidationResult::valid();
    }

    private function validateCustomer(array $events): ValidationResult
    {
        if (!$this->hasEventOfType($events, 'certificate_issued')) {
            return ValidationResult::invalid(['Требуется выданное удостоверение (certificate_issued)']);
        }
        return ValidationResult::valid();
    }

    private function hasEventOfType(array $events, string $type): bool
    {
        return $this->findEventOfType($events, $type) !== null;
    }

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

    private function getPayload(array $event): array
    {
        $payload = $event['payload'] ?? [];
        if (is_string($payload)) {
            return json_decode($payload, true) ?? [];
        }
        return (array) $payload;
    }

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
