<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

/**
 * Результат попытки перехода между стадиями.
 *
 * Иммутабельный объект: содержит либо новую стадию (успех),
 * либо массив ошибок (неудача).
 */
final class TransitionResult
{
    /**
     * @param bool $success Успешность перехода
     * @param string|null $newStage Код новой стадии (при успехе)
     * @param string[] $errors Массив ошибок (при неудаче)
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $newStage = null,
        public readonly array $errors = [],
    ) {}

    /**
     * Создать успешный результат перехода.
     *
     * @param string $newStage Код новой стадии
     * @return self
     */
    public static function ok(string $newStage): self
    {
        return new self(success: true, newStage: $newStage);
    }

    /**
     * Создать неуспешный результат перехода.
     *
     * @param string[] $errors Массив причин отказа
     * @return self
     */
    public static function fail(array $errors): self
    {
        return new self(success: false, errors: $errors);
    }

    /**
     * Сериализовать результат в массив.
     *
     * @return array{success: bool, new_stage: string|null, errors: string[]}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'new_stage' => $this->newStage,
            'errors' => $this->errors,
        ];
    }
}
