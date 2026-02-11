<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\ActionService;

class ActionController
{
    public function __construct(
        private readonly ActionService $actionService,
    ) {}

    /** POST /api/companies/{id}/actions/{action} */
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
