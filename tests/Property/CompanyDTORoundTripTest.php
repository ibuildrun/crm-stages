<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\DTO\CompanyDTO;
use GlavPro\CrmStages\Domain\StageMap;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 9: Company state serialization round-trip
 * Validates: Requirements 12.3
 */
class CompanyDTORoundTripTest extends TestCase
{
    use TestTrait;

    public function testCompanyDTORoundTrip(): void
    {
        $stageMap = new StageMap();
        $stages = $stageMap->getAllStageCodes();

        $this
            ->forAll(
                Generator\choose(1, 100000),
                Generator\suchThat(
                    fn($s) => strlen($s) > 0,
                    Generator\string()
                ),
                Generator\elements(...$stages),
                Generator\choose(1, 1000),
            )
            ->withMaxSize(200)
            ->then(function (int $id, string $name, string $stageCode, int $createdBy) use ($stageMap): void {
                $now = '2025-01-15 10:30:00';
                $stageName = $stageMap->getStageInfo($stageCode)->name;

                $original = new CompanyDTO(
                    id: $id,
                    name: $name,
                    stageCode: $stageCode,
                    stageName: $stageName,
                    createdAt: $now,
                    updatedAt: $now,
                    createdBy: $createdBy,
                );

                $array = $original->toArray();
                $restored = CompanyDTO::fromArray($array);

                $this->assertSame($original->id, $restored->id);
                $this->assertSame($original->name, $restored->name);
                $this->assertSame($original->stageCode, $restored->stageCode);
                $this->assertSame($original->stageName, $restored->stageName);
                $this->assertSame($original->createdAt, $restored->createdAt);
                $this->assertSame($original->updatedAt, $restored->updatedAt);
                $this->assertSame($original->createdBy, $restored->createdBy);
            });
    }
}
