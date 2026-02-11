<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Repository;

use GlavPro\CrmStages\DTO\Event;

class EventRepository
{
    /** @var array<int, Event[]> In-memory storage keyed by company_id */
    private array $storage = [];
    private int $nextId = 1;

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

    /** @return Event[] sorted by created_at DESC */
    public function findByCompanyId(int $companyId): array
    {
        $events = $this->storage[$companyId] ?? [];
        usort($events, fn(Event $a, Event $b) => $b->createdAt <=> $a->createdAt);
        return $events;
    }

    /** @return Event[] filtered by type, sorted by created_at DESC */
    public function findByCompanyIdAndType(int $companyId, string $type): array
    {
        $events = $this->findByCompanyId($companyId);
        return array_values(array_filter($events, fn(Event $e) => $e->type === $type));
    }
}
