<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\Domain\TransitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 4: Demo freshness constraint — 60-day window
 * Validates: Requirements 7.2, 7.3
 */
class DemoFreshnessTest extends TestCase
{
    use TestTrait;

    private TransitionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TransitionValidator();
    }

    /**
     * For any demo conducted within 60 days with an invoice, transition should succeed.
     */
    public function testDemoWithin60DaysAllowsTransition(): void
    {
        $this
            ->forAll(Generator\choose(0, 59))
            ->then(function (int $daysAgo): void {
                $now = new \DateTimeImmutable('2025-06-15 12:00:00');
                $demoDate = $now->modify("-{$daysAgo} days")->format('Y-m-d H:i:s');

                $events = [
                    ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $demoDate],
                    ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
                ];

                $result = $this->validator->validateDemoDoneAt($events, $now);
                $this->assertTrue(
                    $result->isValid,
                    "Demo {$daysAgo} days ago should be valid, got: " . implode(', ', $result->errors)
                );
            });
    }

    /**
     * For any demo conducted more than 60 days ago, transition should fail.
     */
    public function testDemoOlderThan60DaysRejectsTransition(): void
    {
        $this
            ->forAll(Generator\choose(61, 365))
            ->then(function (int $daysAgo): void {
                $now = new \DateTimeImmutable('2025-06-15 12:00:00');
                $demoDate = $now->modify("-{$daysAgo} days")->format('Y-m-d H:i:s');

                $events = [
                    ['type' => 'demo_conducted', 'payload' => [], 'created_at' => $demoDate],
                    ['type' => 'invoice_created', 'payload' => [], 'created_at' => $now->format('Y-m-d H:i:s')],
                ];

                $result = $this->validator->validateDemoDoneAt($events, $now);
                $this->assertFalse(
                    $result->isValid,
                    "Demo {$daysAgo} days ago should be rejected"
                );
            });
    }
}
