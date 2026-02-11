<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Unit;

use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\Domain\StageEngine;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for early stage transitions: Ice → Touched → Aware → Interested.
 * Validates: Requirements 2.3, 2.4, 3.3, 3.4, 4.3, 4.4
 */
final class EarlyStageTransitionsTest extends TestCase
{
    private StageEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new StageEngine(
            new TransitionValidator(),
            new StageMap(),
            new ActionRestrictions(),
        );
    }

    // ── Ice → Touched ──────────────────────────────────────────────

    public function testIceToTouchedSucceedsWithLprConversation(): void
    {
        $events = [['type' => 'lpr_conversation', 'payload' => ['comment' => 'Spoke with CEO'], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transition('Ice', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Touched', $result->newStage);
    }

    public function testIceToTouchedFailsWithoutLprConversation(): void
    {
        $events = [['type' => 'contact_attempt', 'payload' => [], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transition('Ice', $events);

        $this->assertFalse($result->success);
        $this->assertNull($result->newStage);
        $this->assertNotEmpty($result->errors);
    }

    public function testIceToTouchedFailsWithEmptyEvents(): void
    {
        $result = $this->engine->transition('Ice', []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('ЛПР', $result->errors[0]);
    }

    public function testIceCannotSkipToAware(): void
    {
        $events = [['type' => 'lpr_conversation', 'payload' => [], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transitionTo('Ice', 'Aware', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('последовательный', $result->errors[0]);
    }

    // ── Touched → Aware ────────────────────────────────────────────

    public function testTouchedToAwareSucceedsWithDiscoveryFilled(): void
    {
        $events = [['type' => 'discovery_filled', 'payload' => ['needs' => 'Safety training'], 'created_at' => '2026-02-02 10:00:00']];
        $result = $this->engine->transition('Touched', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Aware', $result->newStage);
    }

    public function testTouchedToAwareFailsWithoutDiscovery(): void
    {
        $events = [['type' => 'lpr_conversation', 'payload' => [], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transition('Touched', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('дискавери', $result->errors[0]);
    }

    public function testTouchedToAwareFailsWithEmptyEvents(): void
    {
        $result = $this->engine->transition('Touched', []);

        $this->assertFalse($result->success);
    }

    // ── Aware → Interested ─────────────────────────────────────────

    public function testAwareToInterestedSucceedsWithDemoPlanned(): void
    {
        $events = [['type' => 'demo_planned', 'payload' => ['scheduled_at' => '2026-02-15 14:00:00'], 'created_at' => '2026-02-03 10:00:00']];
        $result = $this->engine->transition('Aware', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Interested', $result->newStage);
    }

    public function testAwareToInterestedFailsWithoutDemoPlanned(): void
    {
        $events = [['type' => 'discovery_filled', 'payload' => [], 'created_at' => '2026-02-02 10:00:00']];
        $result = $this->engine->transition('Aware', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('демонстрация', $result->errors[0]);
    }

    // ── Interested → demo_planned ──────────────────────────────────

    public function testInterestedToDemoPlannedSucceedsWithScheduledDate(): void
    {
        $events = [['type' => 'demo_planned', 'payload' => ['scheduled_at' => '2026-02-20 15:00:00'], 'created_at' => '2026-02-05 10:00:00']];
        $result = $this->engine->transition('Interested', $events);

        $this->assertTrue($result->success);
        $this->assertSame('demo_planned', $result->newStage);
    }

    public function testInterestedToDemoPlannedFailsWithoutScheduledDate(): void
    {
        $events = [['type' => 'demo_planned', 'payload' => [], 'created_at' => '2026-02-05 10:00:00']];
        $result = $this->engine->transition('Interested', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('scheduled_at', $result->errors[0]);
    }

    public function testInterestedToDemoPlannedFailsWithNoDemoEvent(): void
    {
        $result = $this->engine->transition('Interested', []);

        $this->assertFalse($result->success);
    }
}
