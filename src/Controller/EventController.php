<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\EventService;

class EventController
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /** GET /api/companies/{id}/events?type={type} */
    public function index(int $companyId, ?string $typeFilter = null): array
    {
        $events = $this->eventService->getEvents($companyId, $typeFilter);
        return [
            'success' => true,
            'data' => array_map(fn($e) => $e->toArray(), $events),
        ];
    }
}
