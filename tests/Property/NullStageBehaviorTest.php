<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\Domain\StageEngine;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 5: Null stage reachable from any active stage and terminal
 * Validates: Requirements 13.1, 13.4
 */
class NullStageBehaviorTest extends TestCase
{
    use TestTrait;

    private StageEngine $engine;
    private StageMap $stageMap;

    protected function setUp(): void
    {
        $this->stageMap = new StageMap();
        $this->engine = new StageEngine(
            new TransitionValidator(),
            $this->stageMap,
            new ActionRestrictions(),
        );
    }

    /**
     * For any non-terminal, non-Null stage, transitionToNull should succeed.
     */
    public function testNullReachableFromAnyActiveStage(): void
    {
        $activeStages = array_filter(
            $this->stageMap->getOrderedStages(),
            fn(string $s) => $s !== 'Activated'
        );

        $this
            ->forAll(Generator\elements(...array_values($activeStages)))
            ->then(function (string $stage): void {
                $result = $this->engine->transitionToNull($stage);
                $this->assertTrue(
                    $result->success,
                    "Should be able to transition from {$stage} to Null"
                );
                $this->assertSame('Null', $result->newStage);
            });
    }

    /**
     * For a company already in Null, no transitions should be possible.
     */
    public function testNullIsTerminal(): void
    {
        $result = $this->engine->transitionToNull('Null');
        $this->assertFalse($result->success, 'Null to Null should be rejected');

        $result = $this->engine->transition('Null', []);
        $this->assertFalse($result->success, 'Null forward transition should be rejected');
    }
}
