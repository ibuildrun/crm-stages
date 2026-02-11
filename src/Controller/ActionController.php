<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\ActionService;

/**
 * Контроллер действий над компанией.
 *
 * Обрабатывает HTTP-запросы для выполнения действий
 * (звонок, демо, счёт и т.д.) с учётом ограничений стадии.
 */
class ActionController
{
    /**
     * @param ActionService $actionService Сервис выполнения действий
     */
    public function __construct(
        private readonly ActionService $actionService,
    ) {}

    /**
     * Выполнить действие над компанией.
     *
     * POST /api/companies/{id}/actions/{action}
     *
     * @param int $companyId Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @param string $action Код действия
     * @param array<string, mixed> $data Дополнительные данные действия
     * @return array{success: bool, data?: array, errors?: array} Ответ API
     */
    public function execute(int $companyId, int $managerId, string $action, array $data = []): array
    {
        try {
            $event = $this->actionService->executeAction($companyId, $managerId, $action, $data);
            return ['success' => true, 'data' => $event->toArray()];
        } catch (\RuntimeException $e) {
            return [
                'success' => false,
                'errors' => [['code' => 'ACTION_RESTRICTED', 'message' => $e->getMessage()]],
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'errors' => [['code' => 'INVALID_ACTION', 'message' => $e->getMessage()]],
            ];
        }
    }
}
