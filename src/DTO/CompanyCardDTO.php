<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

use GlavPro\CrmStages\Domain\StageInfo;

final class CompanyCardDTO
{
    /**
     * @param string[] $availableActions
     * @param Event[] $events
     */
    public function __construct(
        public readonly CompanyDTO $company,
        public readonly StageInfo $stageInfo,
        public readonly array $availableActions,
        public readonly string $instruction,
        public readonly array $events,
    ) {}

    public function toArray(): array
    {
        return [
            'company' => $this->company->toArray(),
            'stage_info' => $this->stageInfo->toArray(),
            'available_actions' => $this->availableActions,
            'instruction' => $this->instruction,
            'events' => array_map(fn(Event $e) => $e->toArray(), $this->events),
        ];
    }
}
