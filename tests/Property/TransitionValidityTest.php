<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 1: Transition validity — transition succeeds iff exit conditions met
 * Validates: Requirements 1.2, 1.3
 */
class TransitionValidityTest extends TestCase
{
    use TestTrait;

    private TransitionValidator $validator;
    private StageMap $stageMap;

    protected function setUp(): void
    {
        $this->validator = new TransitionValidator();
        $this->stageMap = new StageMap();
    }

    /**
     * For any non-terminal stage, providing the required events should make validation pass.
     */
    public function testTransitionSucceedsWhenConditionsMet(): void
    {
        $nonTerminalStages = ['Ice', 'Touched', 'Aware', 'Interested', 'demo_planned', 'Committed', 'Customer'];

        $this
            ->forAll(Generator\elements(...$nonTerminalStages))
            ->then(function (string $stage): void {
                $events = $this->buildSatisfyingEvents($stage);
                $result = $this->validator->validate($stage, $events);
                $this->assertTrue(
                    $result->isValid,
                    "Stage {$stage} should pass with correct events, got errors: " . implode(', ', $result->errors)
                );
            });
    }

    /**
     * For any non-terminal stage, providing NO events should make validation fail.
     */
    public function testTransitionFailsWhenConditionsNotMet(): void
    {
        $nonTerminalStages = ['Ice', 'Touched', 'Aware', 'Interested', 'demo_planned', 'Committed', 'Customer'];

        $this
            ->forAll(Generator\elements(...$nonTerminalStages))
            ->then(function (string $stage): void {
                $result = $this->validator->validate($stage, []);
                $this->assertFalse(
                    $result->isValid,
                    "Stage {$stage} should fail with no events"
                );
                $this->assertNotEmpty($result->errors, "Errors should be descriptive for stage {$stage}");
            });
    }

    private function buildSatisfyingEvents(string $stage): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        return match ($stage) {
            'Ice' => [['type' => 'lpr_conversation', 'payload' => ['comment' => 'test'], 'created_at' => $now]],
            'Touched' => [['type' => 'discovery_filled', 'payload' => [], 'created_at' => $now]],
            'Aware' => [['type' => 'demo_planned', 'payload' => ['scheduled_at' => $now], 'created_at' => $now]],
            'Interested' => [['type' => 'demo_planned', 'payload' => ['scheduled_at' => $now], 'created_at' => $now]],
            'demo_planned' => [['type' => 'demo_conducted', 'payload' => [], 'created_at' => $now]],
            'Demo_done' => [
                ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $now],
                ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now],
            ],
            'Committed' => [['type' => 'payment_received', 'payload' => [], 'created_at' => $now]],
            'Customer' => [['type' => 'certificate_issued', 'payload' => [], 'created_at' => $now]],
            default => [],
        };
    }
}
