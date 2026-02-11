<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Service;

use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\DTO\Event;
use GlavPro\CrmStages\Repository\CompanyRepository;

class ActionService
{
    private const ACTION_TO_EVENT = [
        'call' => 'contact_attempt',
        'fill_discovery' => 'discovery_filled',
        'plan_demo' => 'demo_planned',
        'conduct_demo' => 'demo_conducted',
        'create_invoice' => 'invoice_created',
        'send_kp' => 'kp_sent',
        'record_payment' => 'payment_received',
        'issue_certificate' => 'certificate_issued',
    ];

    public function __construct(
        private readonly ActionRestrictions $restrictions,
        private readonly EventService $eventService,
        private readonly CompanyRepository $companyRepo,
    ) {}

    public function executeAction(int $companyId, int $managerId, string $action, array $data = []): Event
    {
        $company = $this->companyRepo->findById($companyId);
        if ($company === null) {
            throw new \RuntimeException("Company {$companyId} not found");
        }

        if (!$this->restrictions->isActionAllowed($company->stageCode, $action)) {
            $allowed = $this->restrictions->getAllowedActions($company->stageCode);
            throw new \RuntimeException(
                "Action '{$action}' is restricted at stage {$company->stageCode}. Allowed: " . implode(', ', $allowed)
            );
        }

        $eventType = self::ACTION_TO_EVENT[$action] ?? null;
        if ($eventType === null) {
            throw new \InvalidArgumentException("Unknown action: {$action}");
        }

        return $this->eventService->recordEvent($companyId, $managerId, $eventType, $data);
    }
}
