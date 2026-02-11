<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

final class Event
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly int $managerId,
        public readonly string $type,
        public readonly array $payload,
        public readonly string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'manager_id' => $this->managerId,
            'type' => $this->type,
            'payload' => $this->payload,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $payload = $data['payload'];
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?? [];
        }

        return new self(
            id: (int) $data['id'],
            companyId: (int) $data['company_id'],
            managerId: (int) $data['manager_id'],
            type: (string) $data['type'],
            payload: (array) $payload,
            createdAt: (string) $data['created_at'],
        );
    }
}
