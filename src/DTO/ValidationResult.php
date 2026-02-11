<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

/**
 * Результат валидации условий выхода из стадии.
 *
 * Иммутабельный объект: valid (условия выполнены)
 * или invalid с массивом ошибок.
 */
final class ValidationResult
{
    /**
     * @param bool $isValid Пройдена ли валидация
     * @param string[] $errors Массив ошибок (при неудаче)
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
    ) {}

    /**
     * Создать успешный результат валидации.
     *
     * @return self
     */
    public static function valid(): self
    {
        return new self(isValid: true);
    }

    /**
     * Создать неуспешный результат валидации.
     *
     * @param string[] $errors Массив причин отказа
     * @return self
     */
    public static function invalid(array $errors): self
    {
        return new self(isValid: false, errors: $errors);
    }
}
