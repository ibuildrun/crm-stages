<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Service;

use GlavPro\CrmStages\DTO\Event;
use GlavPro\CrmStages\Repository\EventRepository;

/**
 * Сервис событий компании.
 *
 * Управляет записью и получением событий (append-only).
 * Валидирует типы событий перед записью.
 */
class EventService
{
    /** @var string[] Допустимые типы событий */
    private const VALID_TYPES = [
        'contact_attempt', 'lpr_conversation', 'discovery_filled',
        'demo_planned', 'demo_conducted', 'invoice_created',
        'kp_sent', 'payment_received', 'certificate_issued', 'stage_transition',
    ];

    /**
     * @param EventRepository $repository Репозиторий событий
     */
    public function __construct(
        private readonly EventRepository $repository,
    ) {}

    /**
     * Записать новое событие компании.
     *
     * @param int $companyId Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @param string $type Тип события (из VALID_TYPES)
     * @param array<string, mixed> $payload Дополнительные данные события
     * @return Event Созданное событие
     * @throws \InvalidArgumentException Если тип события недопустим
     */
    public function recordEvent(int $companyId, int $managerId, string $type, array $payload = []): Event
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Неверный тип события: {$type}");
        }

        return $this->repository->insert($companyId, $managerId, $type, $payload);
    }

    /**
     * Получить события компании.
     *
     * @param int $companyId Идентификатор компании
     * @param string|null $typeFilter Фильтр по типу события (опционально)
     * @return Event[] Массив событий
     */
    public function getEvents(int $companyId, ?string $typeFilter = null): array
    {
        if ($typeFilter !== null) {
            return $this->repository->findByCompanyIdAndType($companyId, $typeFilter);
        }
        return $this->repository->findByCompanyId($companyId);
    }

    /**
     * Получить события компании в виде массивов для TransitionValidator.
     *
     * @param int $companyId Идентификатор компании
     * @return array<array<string, mixed>> Массив событий в формате массивов
     */
    public function getEventsAsArrays(int $companyId): array
    {
        return array_map(fn(Event $e) => $e->toArray(), $this->getEvents($companyId));
    }
}
