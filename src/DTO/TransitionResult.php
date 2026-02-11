<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

final class TransitionResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $newStage = null,
        public readonly array $errors = [],
    ) {}

    public static function ok(string $newStage): self
    {
        return new self(success: true, newStage: $newStage);
    }

    /** @param string[] $errors */
    public static function fail(array $errors): self
    {
        return new self(success: false, errors: $errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'new_stage' => $this->newStage,
            'errors' => $this->errors,
        ];
    }
}
