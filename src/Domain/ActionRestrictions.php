<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

final class ActionRestrictions
{
    public const ALL_ACTIONS = [
        'call', 'fill_discovery', 'plan_demo', 'conduct_demo',
        'create_invoice', 'send_kp', 'record_payment', 'issue_certificate',
    ];

    /**
     * Restricted actions per stage.
     * @var array<string, string[]>
     */
    private const RESTRICTIONS = [
        'Ice'          => ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
        'Touched'      => ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
        'Aware'        => ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
        'Interested'   => ['create_invoice', 'send_kp'],
        'demo_planned' => ['create_invoice', 'send_kp'],
        'Demo_done'    => [],
        'Committed'    => [],
        'Customer'     => [],
        'Activated'    => [],
        'Null'         => ['call', 'fill_discovery', 'plan_demo', 'conduct_demo', 'create_invoice', 'send_kp', 'record_payment', 'issue_certificate'],
    ];

    /** @return string[] */
    public function getAllowedActions(string $stageCode): array
    {
        $restricted = $this->getRestrictedActions($stageCode);
        return array_values(array_diff(self::ALL_ACTIONS, $restricted));
    }

    /** @return string[] */
    public function getRestrictedActions(string $stageCode): array
    {
        return self::RESTRICTIONS[$stageCode] ?? [];
    }

    public function isActionAllowed(string $stageCode, string $action): bool
    {
        return !in_array($action, $this->getRestrictedActions($stageCode), true);
    }
}
