<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\Domain\StageMap;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 3: Action restrictions enforced per stage
 * Validates: Requirements 3.2, 4.2, 5.2, 14.1, 14.2, 14.3, 14.4, 14.5
 */
class ActionRestrictionsTest extends TestCase
{
    use TestTrait;

    private ActionRestrictions $restrictions;
    private StageMap $stageMap;

    protected function setUp(): void
    {
        $this->restrictions = new ActionRestrictions();
        $this->stageMap = new StageMap();
    }

    /**
     * For any stage and any action, allowed + restricted = all actions (partition).
     */
    public function testAllowedAndRestrictedPartitionAllActions(): void
    {
        $allStages = $this->stageMap->getAllStageCodes();

        $this
            ->forAll(Generator\elements(...$allStages))
            ->then(function (string $stage): void {
                $allowed = $this->restrictions->getAllowedActions($stage);
                $restricted = $this->restrictions->getRestrictedActions($stage);

                $combined = array_merge($allowed, $restricted);
                sort($combined);
                $allActions = ActionRestrictions::ALL_ACTIONS;
                sort($allActions);

                $this->assertSame(
                    $allActions,
                    $combined,
                    "Allowed + restricted should equal all actions for stage {$stage}"
                );

                // No overlap
                $overlap = array_intersect($allowed, $restricted);
                $this->assertEmpty($overlap, "No action should be both allowed and restricted for stage {$stage}");
            });
    }

    /**
     * For any stage and any action, isActionAllowed should be consistent with getAllowedActions.
     */
    public function testIsActionAllowedConsistency(): void
    {
        $allStages = $this->stageMap->getAllStageCodes();

        $this
            ->forAll(
                Generator\elements(...$allStages),
                Generator\elements(...ActionRestrictions::ALL_ACTIONS),
            )
            ->then(function (string $stage, string $action): void {
                $allowed = $this->restrictions->getAllowedActions($stage);
                $isAllowed = $this->restrictions->isActionAllowed($stage, $action);

                $this->assertSame(
                    in_array($action, $allowed, true),
                    $isAllowed,
                    "isActionAllowed should match getAllowedActions for {$stage}/{$action}"
                );
            });
    }

    /**
     * Ice and Touched must restrict invoices, KP, demos.
     */
    public function testIceAndTouchedRestrictions(): void
    {
        $mustRestrict = ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'];

        $this
            ->forAll(Generator\elements('Ice', 'Touched'))
            ->then(function (string $stage) use ($mustRestrict): void {
                foreach ($mustRestrict as $action) {
                    $this->assertFalse(
                        $this->restrictions->isActionAllowed($stage, $action),
                        "{$action} should be restricted in {$stage}"
                    );
                }
            });
    }

    /**
     * Demo_done must allow invoices and KP.
     */
    public function testDemoDoneAllowsInvoicesAndKP(): void
    {
        $this->assertTrue($this->restrictions->isActionAllowed('Demo_done', 'create_invoice'));
        $this->assertTrue($this->restrictions->isActionAllowed('Demo_done', 'send_kp'));
    }
}
