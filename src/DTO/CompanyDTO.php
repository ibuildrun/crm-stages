<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\DTO;

/**
 * DTO компании.
 *
 * Иммутабельный объект с основными данными компании в воронке.
 *
 * @property int $id Идентификатор компании
 * @property string $name Название компании
 * @property string $stageCode Код текущей стадии
 * @property string $stageName Название текущей стадии
 * @property string $createdAt Дата создания (ISO 8601)
 * @property string $updatedAt Дата последнего обновления (ISO 8601)
 * @property int $createdBy Идентификатор менеджера-создателя
 */
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

    /**
     * Сериализовать DTO в массив.
     *
     * @return array{id: int, name: string, stage_code: string, stage_name: string, created_at: string, updated_at: string, created_by: int}
     */
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

    /**
     * Создать DTO из массива данных.
     *
     * @param array{id: int|string, name: string, stage_code: string, stage_name: string, created_at: string, updated_at: string, created_by: int|string} $data Массив с данными компании
     * @return self
     */
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
