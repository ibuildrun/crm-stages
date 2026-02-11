<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Service;

use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\DTO\Event;
use GlavPro\CrmStages\Repository\CompanyRepository;

/**
 * Сервис выполнения действий над компанией.
 *
 * Проверяет допустимость действия на текущей стадии,
 * маппит действие в тип события и записывает его.
 */
class ActionService
{
    /** @var array<string, string> Маппинг действий в типы событий */
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

    /**
     * @param ActionRestrictions $restrictions Ограничения действий по стадиям
     * @param EventService $eventService Сервис событий
     * @param CompanyRepository $companyRepo Репозиторий компаний
     */
    public function __construct(
        private readonly ActionRestrictions $restrictions,
        private readonly EventService $eventService,
        private readonly CompanyRepository $companyRepo,
    ) {}

    /**
     * Выполнить действие над компанией.
     *
     * Проверяет допустимость действия на текущей стадии,
     * маппит в тип события и записывает через EventService.
     *
     * @param int $companyId Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @param string $action Код действия (например, 'call', 'plan_demo')
     * @param array<string, mixed> $data Дополнительные данные действия
     * @return Event Созданное событие
     * @throws \RuntimeException Если компания не найдена или действие запрещено
     * @throws \InvalidArgumentException Если действие неизвестно
     */
    public function executeAction(int $companyId, int $managerId, string $action, array $data = []): Event
    {
        $company = $this->companyRepo->findById($companyId);
        if ($company === null) {
            throw new \RuntimeException("Компания {$companyId} не найдена");
        }

        if (!$this->restrictions->isActionAllowed($company->stageCode, $action)) {
            $allowed = $this->restrictions->getAllowedActions($company->stageCode);
            throw new \RuntimeException(
                "Действие '{$action}' недоступно на стадии {$company->stageCode}. Доступны: " . implode(', ', $allowed)
            );
        }

        $eventType = self::ACTION_TO_EVENT[$action] ?? null;
        if ($eventType === null) {
            throw new \InvalidArgumentException("Неизвестное действие: {$action}");
        }

        return $this->eventService->recordEvent($companyId, $managerId, $eventType, $data);
    }
}
