<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Unit;

use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\Domain\StageEngine;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for late stage transitions: Customer → Activated, and Null stage behavior.
 * Validates: Requirements 8.2, 8.3, 9.2, 9.3, 13.1, 13.2, 13.3, 13.4
 */
final class LateStageTransitionsTest extends TestCase
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

    // ── Customer → Activated ───────────────────────────────────────

    public function testCustomerToActivatedSucceedsWithCertificate(): void
    {
        $events = [['type' => 'certificate_issued', 'payload' => ['number' => 'CERT-001'], 'created_at' => '2026-02-20 10:00:00']];
        $result = $this->engine->transition('Customer', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Activated', $result->newStage);
    }

    public function testCustomerToActivatedFailsWithoutCertificate(): void
    {
        $events = [['type' => 'payment_received', 'payload' => [], 'created_at' => '2026-02-15 10:00:00']];
        $result = $this->engine->transition('Customer', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('certificate_issued', $result->errors[0]);
    }

    // ── Activated (terminal) ───────────────────────────────────────

    public function testActivatedIsTerminalNoForwardTransition(): void
    {
        $events = [['type' => 'certificate_issued', 'payload' => [], 'created_at' => '2026-02-20 10:00:00']];
        $result = $this->engine->transition('Activated', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('терминальной', $result->errors[0]);
    }

    public function testActivatedCannotTransitionToNull(): void
    {
        $result = $this->engine->transitionToNull('Activated');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('терминальной', $result->errors[0]);
    }

    // ── Null stage ─────────────────────────────────────────────────

    public function testNullIsTerminalNoTransitions(): void
    {
        $result = $this->engine->transition('Null', []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('терминальной', $result->errors[0]);
    }

    public function testNullCannotTransitionToNull(): void
    {
        $result = $this->engine->transitionToNull('Null');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Null', $result->errors[0]);
    }

    // ── Null reachable from any active stage ───────────────────────

    public function testNullReachableFromIce(): void
    {
        $result = $this->engine->transitionToNull('Ice');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromTouched(): void
    {
        $result = $this->engine->transitionToNull('Touched');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromAware(): void
    {
        $result = $this->engine->transitionToNull('Aware');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromInterested(): void
    {
        $result = $this->engine->transitionToNull('Interested');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromDemoPlanned(): void
    {
        $result = $this->engine->transitionToNull('demo_planned');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromDemoDone(): void
    {
        $result = $this->engine->transitionToNull('Demo_done');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromCommitted(): void
    {
        $result = $this->engine->transitionToNull('Committed');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    public function testNullReachableFromCustomer(): void
    {
        $result = $this->engine->transitionToNull('Customer');
        $this->assertTrue($result->success);
        $this->assertSame('Null', $result->newStage);
    }

    // ── Null stage has no available actions ─────────────────────────

    public function testNullStageHasNoAvailableActions(): void
    {
        $actions = $this->engine->getAvailableActions('Null');
        $this->assertEmpty($actions);
    }

    // ── Stage skipping is rejected ─────────────────────────────────

    public function testCannotSkipFromIceToInterested(): void
    {
        $events = [['type' => 'lpr_conversation', 'payload' => [], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transitionTo('Ice', 'Interested', $events);

        $this->assertFalse($result->success);
    }

    public function testCannotSkipFromTouchedToCommitted(): void
    {
        $events = [['type' => 'discovery_filled', 'payload' => [], 'created_at' => '2026-02-01 10:00:00']];
        $result = $this->engine->transitionTo('Touched', 'Committed', $events);

        $this->assertFalse($result->success);
    }
}
