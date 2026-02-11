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
 * Feature: crm-stages, Property 2: Sequential transitions — no stage skipping
 * Validates: Requirements 1.5, 1.6
 */
class SequentialTransitionsTest extends TestCase
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
     * For any non-adjacent stage pair, transitionTo should be rejected.
     */
    public function testSkippingStagesIsRejected(): void
    {
        $ordered = $this->stageMap->getOrderedStages();

        $this
            ->forAll(
                Generator\elements(...$ordered),
                Generator\elements(...$ordered),
            )
            ->then(function (string $from, string $to) use ($ordered): void {
                $fromIdx = array_search($from, $ordered, true);
                $toIdx = array_search($to, $ordered, true);

                // Only test non-adjacent forward jumps (skip >= 2 stages)
                if ($toIdx - $fromIdx > 1) {
                    $result = $this->engine->transitionTo($from, $to, []);
                    $this->assertFalse(
                        $result->success,
                        "Skipping from {$from} to {$to} should be rejected"
                    );
                }
            });
    }

    /**
     * For any stage, backward transitions should be rejected.
     */
    public function testBackwardTransitionsAreRejected(): void
    {
        $ordered = $this->stageMap->getOrderedStages();

        $this
            ->forAll(
                Generator\elements(...$ordered),
                Generator\elements(...$ordered),
            )
            ->then(function (string $from, string $to) use ($ordered): void {
                $fromIdx = array_search($from, $ordered, true);
                $toIdx = array_search($to, $ordered, true);

                if ($toIdx < $fromIdx) {
                    $result = $this->engine->transitionTo($from, $to, []);
                    $this->assertFalse(
                        $result->success,
                        "Backward transition from {$from} to {$to} should be rejected"
                    );
                }
            });
    }
}
