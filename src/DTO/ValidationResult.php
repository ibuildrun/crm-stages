<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

final class ValidationResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
    ) {}

    public static function valid(): self
    {
        return new self(isValid: true);
    }

    /** @param string[] $errors */
    public static function invalid(array $errors): self
    {
        return new self(isValid: false, errors: $errors);
    }
}
