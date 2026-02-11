<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Service;

use GlavPro\CrmStages\DTO\Event;
use GlavPro\CrmStages\Repository\EventRepository;

class EventService
{
    private const VALID_TYPES = [
        'contact_attempt', 'lpr_conversation', 'discovery_filled',
        'demo_planned', 'demo_conducted', 'invoice_created',
        'kp_sent', 'payment_received', 'certificate_issued', 'stage_transition',
    ];

    public function __construct(
        private readonly EventRepository $repository,
    ) {}

    public function recordEvent(int $companyId, int $managerId, string $type, array $payload = []): Event
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid event type: {$type}");
        }

        return $this->repository->insert($companyId, $managerId, $type, $payload);
    }

    /** @return Event[] */
    public function getEvents(int $companyId, ?string $typeFilter = null): array
    {
        if ($typeFilter !== null) {
            return $this->repository->findByCompanyIdAndType($companyId, $typeFilter);
        }
        return $this->repository->findByCompanyId($companyId);
    }

    /** @return array[] Events as arrays for TransitionValidator */
    public function getEventsAsArrays(int $companyId): array
    {
        return array_map(fn(Event $e) => $e->toArray(), $this->getEvents($companyId));
    }
}
