<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Repository;

use GlavPro\CrmStages\DTO\Event;

/**
 * Репозиторий событий (in-memory реализация).
 *
 * Хранит события компаний в памяти (append-only).
 * Предназначен для замены на реализацию с Joomla DB API.
 */
class EventRepository
{
    /** @var array<int, Event[]> Хранилище событий, ключ — company_id */
    private array $storage = [];

    /** @var int Счётчик для генерации идентификаторов */
    private int $nextId = 1;

    /**
     * Записать новое событие.
     *
     * @param int $companyId Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @param string $type Тип события
     * @param array<string, mixed> $payload Дополнительные данные
     * @return Event Созданное событие
     */
    public function insert(int $companyId, int $managerId, string $type, array $payload): Event
    {
        $event = new Event(
            id: $this->nextId++,
            companyId: $companyId,
            managerId: $managerId,
            type: $type,
            payload: $payload,
            createdAt: (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        );

        $this->storage[$companyId][] = $event;
        return $event;
    }

    /**
     * Получить все события компании, отсортированные по дате (новые первыми).
     *
     * @param int $companyId Идентификатор компании
     * @return Event[] Массив событий
     */
    public function findByCompanyId(int $companyId): array
    {
        $events = $this->storage[$companyId] ?? [];
        usort($events, fn(Event $a, Event $b) => $b->createdAt <=> $a->createdAt);
        return $events;
    }

    /**
     * Получить события компании определённого типа, отсортированные по дате (новые первыми).
     *
     * @param int $companyId Идентификатор компании
     * @param string $type Тип события для фильтрации
     * @return Event[] Массив событий
     */
    public function findByCompanyIdAndType(int $companyId, string $type): array
    {
        $events = $this->findByCompanyId($companyId);
        return array_values(array_filter($events, fn(Event $e) => $e->type === $type));
    }
}
