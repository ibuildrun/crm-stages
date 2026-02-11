<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Unit;

use GlavPro\CrmStages\Domain\ActionRestrictions;
use GlavPro\CrmStages\Domain\StageEngine;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for mid-stage transitions: demo_planned → Demo_done → Committed.
 * Including the 60-day demo freshness rule.
 * Validates: Requirements 5.3, 5.4, 6.3, 6.4, 7.2, 7.3, 7.4, 7.5
 */
final class MidStageTransitionsTest extends TestCase
{
    private StageEngine $engine;
    private TransitionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TransitionValidator();
        $this->engine = new StageEngine(
            $this->validator,
            new StageMap(),
            new ActionRestrictions(),
        );
    }

    // ── demo_planned → Demo_done ───────────────────────────────────

    public function testDemoPlannedToDemoDoneSucceedsWithDemoConducted(): void
    {
        $events = [['type' => 'demo_conducted', 'payload' => [], 'created_at' => '2026-02-10 14:00:00']];
        $result = $this->engine->transition('demo_planned', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Demo_done', $result->newStage);
    }

    public function testDemoPlannedToDemoDoneFailsWithoutDemoConducted(): void
    {
        $events = [['type' => 'demo_planned', 'payload' => ['scheduled_at' => '2026-02-10'], 'created_at' => '2026-02-05 10:00:00']];
        $result = $this->engine->transition('demo_planned', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('demo_conducted', $result->errors[0]);
    }

    // ── Demo_done → Committed ──────────────────────────────────────

    public function testDemoDoneToCommittedSucceedsWithInvoiceAndFreshDemo(): void
    {
        $now = new \DateTimeImmutable();
        $recentDemo = $now->modify('-10 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $recentDemo],
            ['type' => 'invoice_created', 'payload' => ['amount' => 50000], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validate('Demo_done', $events);
        $this->assertTrue($validation->isValid);
    }

    public function testDemoDoneToCommittedSucceedsWithKpAndFreshDemo(): void
    {
        $now = new \DateTimeImmutable();
        $recentDemo = $now->modify('-30 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $recentDemo],
            ['type' => 'kp_sent', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validate('Demo_done', $events);
        $this->assertTrue($validation->isValid);
    }

    public function testDemoDoneToCommittedFailsWithExpiredDemo(): void
    {
        $now = new \DateTimeImmutable();
        $oldDemo = $now->modify('-90 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $oldDemo],
            ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validate('Demo_done', $events);
        $this->assertFalse($validation->isValid);
        $this->assertNotEmpty(array_filter($validation->errors, fn($e) => str_contains($e, 'дней')));
    }

    public function testDemoDoneToCommittedFailsWithoutInvoiceOrKp(): void
    {
        $now = new \DateTimeImmutable();
        $recentDemo = $now->modify('-5 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $recentDemo],
        ];

        $validation = $this->validator->validate('Demo_done', $events);
        $this->assertFalse($validation->isValid);
        $this->assertNotEmpty(array_filter($validation->errors, fn($e) => str_contains($e, 'invoice_created') || str_contains($e, 'kp_sent')));
    }

    public function testDemoDoneAt59DaysIsStillFresh(): void
    {
        $now = new \DateTimeImmutable('2026-04-01 12:00:00');
        $demoDate = $now->modify('-59 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $demoDate],
            ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validateDemoDoneAt($events, $now);
        $this->assertTrue($validation->isValid);
    }

    public function testDemoDoneAt61DaysIsExpired(): void
    {
        $now = new \DateTimeImmutable('2026-04-01 12:00:00');
        $demoDate = $now->modify('-61 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $demoDate],
            ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validateDemoDoneAt($events, $now);
        $this->assertFalse($validation->isValid);
    }

    public function testDemoDoneExactly60DaysIsStillFresh(): void
    {
        $now = new \DateTimeImmutable('2026-04-01 12:00:00');
        $demoDate = $now->modify('-60 days')->format('Y-m-d H:i:s');

        $events = [
            ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $demoDate],
            ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
        ];

        $validation = $this->validator->validateDemoDoneAt($events, $now);
        $this->assertTrue($validation->isValid);
    }

    // ── Committed → Customer ───────────────────────────────────────

    public function testCommittedToCustomerSucceedsWithPayment(): void
    {
        $events = [['type' => 'payment_received', 'payload' => ['amount' => 50000], 'created_at' => '2026-02-15 10:00:00']];
        $result = $this->engine->transition('Committed', $events);

        $this->assertTrue($result->success);
        $this->assertSame('Customer', $result->newStage);
    }

    public function testCommittedToCustomerFailsWithoutPayment(): void
    {
        $events = [['type' => 'invoice_created', 'payload' => [], 'created_at' => '2026-02-10 10:00:00']];
        $result = $this->engine->transition('Committed', $events);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('payment_received', $result->errors[0]);
    }
}
