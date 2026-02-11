<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

/**
 * Ограничения действий по стадиям воронки.
 *
 * Определяет, какие действия (звонок, демо, счёт и т.д.)
 * запрещены или разрешены на каждой стадии.
 */
final class ActionRestrictions
{
    /** @var string[] Полный список всех действий в системе */
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

    /**
     * Получить список разрешённых действий для стадии.
     *
     * @param string $stageCode Код стадии
     * @return string[] Массив кодов разрешённых действий
     */
    public function getAllowedActions(string $stageCode): array
    {
        $restricted = $this->getRestrictedActions($stageCode);
        return array_values(array_diff(self::ALL_ACTIONS, $restricted));
    }

    /**
     * Получить список запрещённых действий для стадии.
     *
     * @param string $stageCode Код стадии
     * @return string[] Массив кодов запрещённых действий
     */
    public function getRestrictedActions(string $stageCode): array
    {
        return self::RESTRICTIONS[$stageCode] ?? [];
    }

    /**
     * Проверить, разрешено ли действие на указанной стадии.
     *
     * @param string $stageCode Код стадии
     * @param string $action Код действия
     * @return bool true, если действие разрешено
     */
    public function isActionAllowed(string $stageCode, string $action): bool
    {
        return !in_array($action, $this->getRestrictedActions($stageCode), true);
    }
}
