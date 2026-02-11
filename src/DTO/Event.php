<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

/**
 * DTO события компании.
 *
 * Иммутабельный объект, представляющий одно событие
 * в истории компании (append-only event sourcing).
 *
 * @property int $id Идентификатор события
 * @property int $companyId Идентификатор компании
 * @property int $managerId Идентификатор менеджера
 * @property string $type Тип события (например, 'lpr_conversation', 'demo_conducted')
 * @property array<string, mixed> $payload Дополнительные данные события
 * @property string $createdAt Дата создания (ISO 8601)
 */
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

    /**
     * Сериализовать событие в массив.
     *
     * @return array{id: int, company_id: int, manager_id: int, type: string, payload: array<string, mixed>, created_at: string}
     */
    public function toArray(): array
            'manager_id' => $this->managerId,
            'type' => $this->type,
            'payload' => $this->payload,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Создать DTO из массива данных.
     *
     * Поддерживает payload как массив и как JSON-строку.
     *
     * @param array{id: int|string, company_id: int|string, manager_id: int|string, type: string, payload: array|string, created_at: string} $data Массив с данными события
     * @return self
     */
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
