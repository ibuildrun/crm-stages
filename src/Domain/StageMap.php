<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

final class StageMap
{
    /** @var array<string, StageInfo> */
    private array $stages;

    /** @var string[] */
    private array $order;

    public function __construct()
    {
        $this->order = [
            'Ice', 'Touched', 'Aware', 'Interested',
            'demo_planned', 'Demo_done', 'Committed',
            'Customer', 'Activated',
        ];

        $this->stages = [
            'Ice' => new StageInfo(
                code: 'Ice',
                mlsCode: 'C0',
                name: 'Ice',
                instruction: 'Совершите звонок компании. При ответе зафиксируйте разговор с ЛПР и оставьте комментарий.',
                exitConditions: ['lpr_conversation'],
                restrictions: ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
            ),
            'Touched' => new StageInfo(
                code: 'Touched',
                mlsCode: 'C1',
                name: 'Touched',
                instruction: 'Заполните форму дискавери на основе разговора с ЛПР.',
                exitConditions: ['discovery_filled'],
                restrictions: ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
            ),
            'Aware' => new StageInfo(
                code: 'Aware',
                mlsCode: 'C2',
                name: 'Aware',
                instruction: 'Запланируйте демонстрацию продукта. Укажите дату и время.',
                exitConditions: ['demo_planned'],
                restrictions: ['create_invoice', 'send_kp'],
            ),
            'Interested' => new StageInfo(
                code: 'Interested',
                mlsCode: 'W1',
                name: 'Interested',
                instruction: 'Подтвердите дату демонстрации и подготовьтесь к проведению.',
                exitConditions: ['demo_planned'],
                restrictions: ['create_invoice', 'send_kp'],
            ),
            'demo_planned' => new StageInfo(
                code: 'demo_planned',
                mlsCode: 'W2',
                name: 'Demo Planned',
                instruction: 'Проведите демонстрацию по ссылке. Нажмите кнопку проведения демо.',
                exitConditions: ['demo_conducted'],
                restrictions: ['create_invoice', 'send_kp'],
            ),
            'Demo_done' => new StageInfo(
                code: 'Demo_done',
                mlsCode: 'W3',
                name: 'Demo Done',
                instruction: 'Создайте счёт или отправьте коммерческое предложение. Демо действительно 60 дней.',
                exitConditions: ['invoice_created|kp_sent'],
                restrictions: [],
            ),
            'Committed' => new StageInfo(
                code: 'Committed',
                mlsCode: 'H1',
                name: 'Committed',
                instruction: 'Ожидайте оплату от клиента. Отслеживайте статус счёта.',
                exitConditions: ['payment_received'],
                restrictions: [],
            ),
            'Customer' => new StageInfo(
                code: 'Customer',
                mlsCode: 'H2',
                name: 'Customer',
                instruction: 'Оформите и выдайте удостоверение клиенту.',
                exitConditions: ['certificate_issued'],
                restrictions: [],
            ),
            'Activated' => new StageInfo(
                code: 'Activated',
                mlsCode: 'A1',
                name: 'Activated',
                instruction: 'Клиент активирован. Сделка завершена.',
                exitConditions: [],
                restrictions: [],
            ),
            'Null' => new StageInfo(
                code: 'Null',
                mlsCode: 'N0',
                name: 'Null (Отказ)',
                instruction: 'Сделка отклонена или потеряна.',
                exitConditions: [],
                restrictions: ['call', 'fill_discovery', 'plan_demo', 'conduct_demo', 'create_invoice', 'send_kp', 'record_payment', 'issue_certificate'],
            ),
        ];
    }

    /** @return string[] */
    public function getOrderedStages(): array
    {
        return $this->order;
    }

    public function getNextStage(string $stageCode): ?string
    {
        $index = array_search($stageCode, $this->order, true);
        if ($index === false || $index >= count($this->order) - 1) {
            return null;
        }
        return $this->order[$index + 1];
    }

    public function isTerminal(string $stageCode): bool
    {
        return $stageCode === 'Null' || $stageCode === 'Activated';
    }

    public function getStageIndex(string $stageCode): int
    {
        $index = array_search($stageCode, $this->order, true);
        if ($index === false) {
            if ($stageCode === 'Null') {
                return -1;
            }
            throw new \InvalidArgumentException("Неизвестная стадия: {$stageCode}");
        }
        return (int) $index;
    }

    public function getStageInfo(string $stageCode): StageInfo
    {
        if (!isset($this->stages[$stageCode])) {
            throw new \InvalidArgumentException("Неизвестная стадия: {$stageCode}");
        }
        return $this->stages[$stageCode];
    }

    public function isValidStage(string $stageCode): bool
    {
        return isset($this->stages[$stageCode]);
    }

    /** @return string[] All stage codes including Null */
    public function getAllStageCodes(): array
    {
        return array_keys($this->stages);
    }
}
