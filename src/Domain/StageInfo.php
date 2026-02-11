<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

/**
 * Информация о стадии воронки.
 *
 * Иммутабельный объект с метаданными стадии: код, MLS-код,
 * название, инструкция, условия выхода и ограничения.
 */
final class StageInfo
{
    /**
     * @param string $code Код стадии (например, 'Ice', 'Touched')
     * @param string $mlsCode MLS-код стадии (например, 'C0', 'W1')
     * @param string $name Человекочитаемое название стадии
     * @param string $instruction Инструкция менеджеру для текущей стадии
     * @param string[] $exitConditions Условия выхода из стадии
     * @param string[] $restrictions Ограничения действий на стадии
     */
    public function __construct(
        public readonly string $code,
        public readonly string $mlsCode,
        public readonly string $name,
        public readonly string $instruction,
        public readonly array $exitConditions,
        public readonly array $restrictions,
    ) {}

    /**
     * Сериализовать информацию о стадии в массив.
     *
     * @return array{code: string, mls_code: string, name: string, instruction: string, exit_conditions: string[], restrictions: string[]}
     */
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
