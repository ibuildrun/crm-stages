<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

use GlavPro\CrmStages\Domain\StageInfo;

/**
 * DTO карточки компании.
 *
 * Агрегирует данные компании, информацию о стадии,
 * доступные действия и историю событий для отображения карточки.
 */
final class CompanyCardDTO
{
    /**
     * @param CompanyDTO $company Данные компании
     * @param StageInfo $stageInfo Метаданные текущей стадии
     * @param string[] $availableActions Список разрешённых действий
     * @param string $instruction Инструкция менеджеру
     * @param Event[] $events История событий компании
     */
    public function __construct(
        public readonly CompanyDTO $company,
        public readonly StageInfo $stageInfo,
        public readonly array $availableActions,
        public readonly string $instruction,
        public readonly array $events,
    ) {}

    /**
     * Сериализовать карточку компании в массив.
     *
     * @return array{company: array, stage_info: array, available_actions: string[], instruction: string, events: array[]}
     */
    public function toArray(): array
            'stage_info' => $this->stageInfo->toArray(),
            'available_actions' => $this->availableActions,
            'instruction' => $this->instruction,
            'events' => array_map(fn(Event $e) => $e->toArray(), $this->events),
        ];
    }
}
