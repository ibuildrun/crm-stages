<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Repository;

use GlavPro\CrmStages\DTO\CompanyDTO;

class CompanyRepository
{
    /** @var array<int, array> In-memory storage */
    private array $storage = [];
    private int $nextId = 1;

    public function create(string $name, int $createdBy): CompanyDTO
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $id = $this->nextId++;
        $data = [
            'id' => $id,
            'name' => $name,
            'stage_code' => 'Ice',
            'stage_name' => 'Ice',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $createdBy,
        ];
        $this->storage[$id] = $data;
        return CompanyDTO::fromArray($data);
    }

    public function findById(int $id): ?CompanyDTO
    {
        if (!isset($this->storage[$id])) {
            return null;
        }
        return CompanyDTO::fromArray($this->storage[$id]);
    }

    /**
     * Update stage with optimistic locking.
     * @throws \RuntimeException if current stage doesn't match expected
     */
    public function updateStage(int $id, string $expectedStage, string $newStage, string $newStageName): CompanyDTO
    {
        if (!isset($this->storage[$id])) {
            throw new \RuntimeException("Company {$id} not found");
        }

        if ($this->storage[$id]['stage_code'] !== $expectedStage) {
            throw new \RuntimeException(
                "Stage conflict: expected {$expectedStage}, found {$this->storage[$id]['stage_code']}"
            );
        }

        $this->storage[$id]['stage_code'] = $newStage;
        $this->storage[$id]['stage_name'] = $newStageName;
        $this->storage[$id]['updated_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        return CompanyDTO::fromArray($this->storage[$id]);
    }
}
