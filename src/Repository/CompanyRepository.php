<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Repository;

use GlavPro\CrmStages\DTO\CompanyDTO;

/**
 * Репозиторий компаний (in-memory реализация).
 *
 * Хранит данные компаний в памяти. Предназначен для замены
 * на реализацию с Joomla DB API в продакшене.
 */
class CompanyRepository
{
    /** @var array<int, array<string, mixed>> Хранилище данных компаний */
    private array $storage = [];

    /** @var int Счётчик для генерации идентификаторов */
    private int $nextId = 1;

    /**
     * Создать новую компанию.
     *
     * Компания создаётся на стадии Ice.
     *
     * @param string $name Название компании
     * @param int $createdBy Идентификатор менеджера-создателя
     * @return CompanyDTO Созданная компания
     */
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

    /**
     * Найти компанию по идентификатору.
     *
     * @param int $id Идентификатор компании
     * @return CompanyDTO|null Данные компании или null, если не найдена
     */
    public function findById(int $id): ?CompanyDTO
    {
        if (!isset($this->storage[$id])) {
            return null;
        }
        return CompanyDTO::fromArray($this->storage[$id]);
    }

    /**
     * Обновить стадию компании с оптимистичной блокировкой.
     *
     * @param int $id Идентификатор компании
     * @param string $expectedStage Ожидаемая текущая стадия (для оптимистичной блокировки)
     * @param string $newStage Код новой стадии
     * @param string $newStageName Название новой стадии
     * @return CompanyDTO Обновлённая компания
     * @throws \RuntimeException Если компания не найдена или текущая стадия не совпадает с ожидаемой
     */
    public function updateStage(int $id, string $expectedStage, string $newStage, string $newStageName): CompanyDTO
    {
        if (!isset($this->storage[$id])) {
            throw new \RuntimeException("Компания {$id} не найдена");
        }

        if ($this->storage[$id]['stage_code'] !== $expectedStage) {
            throw new \RuntimeException(
                "Конфликт стадий: ожидалась {$expectedStage}, найдена {$this->storage[$id]['stage_code']}"
            );
        }

        $this->storage[$id]['stage_code'] = $newStage;
        $this->storage[$id]['stage_name'] = $newStageName;
        $this->storage[$id]['updated_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        return CompanyDTO::fromArray($this->storage[$id]);
    }
}
