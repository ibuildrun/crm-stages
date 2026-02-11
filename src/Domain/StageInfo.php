<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

final class StageInfo
{
    /**
     * @param string[] $exitConditions
     * @param string[] $restrictions
     */
    public function __construct(
        public readonly string $code,
        public readonly string $mlsCode,
        public readonly string $name,
        public readonly string $instruction,
        public readonly array $exitConditions,
        public readonly array $restrictions,
    ) {}

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'mls_code' => $this->mlsCode,
            'name' => $this->name,
            'instruction' => $this->instruction,
            'exit_conditions' => $this->exitConditions,
            'restrictions' => $this->restrictions,
        ];
    }
}
