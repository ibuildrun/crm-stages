<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\EventService;

/**
 * Контроллер событий компании.
 *
 * Обрабатывает HTTP-запросы для получения истории событий.
 */
class EventController
{
    /**
     * @param EventService $eventService Сервис событий
     */
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * Получить список событий компании.
     *
     * GET /api/companies/{id}/events?type={type}
     *
     * @param int $companyId Идентификатор компании
     * @param string|null $typeFilter Фильтр по типу события (опционально)
     * @return array{success: bool, data: array} Ответ API
     */
    public function index(int $companyId, ?string $typeFilter = null): array
    {
        $events = $this->eventService->getEvents($companyId, $typeFilter);
        return [
            'success' => true,
            'data' => array_map(fn($e) => $e->toArray(), $events),
        ];
    }
}
