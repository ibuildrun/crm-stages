<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

final class CompanyDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $stageCode,
        public readonly string $stageName,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly int $createdBy,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stage_code' => $this->stageCode,
            'stage_name' => $this->stageName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            stageCode: (string) $data['stage_code'],
            stageName: (string) $data['stage_name'],
            createdAt: (string) $data['created_at'],
            updatedAt: (string) $data['updated_at'],
            createdBy: (int) $data['created_by'],
        );
    }
}
