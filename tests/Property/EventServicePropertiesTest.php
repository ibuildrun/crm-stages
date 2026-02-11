<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\Repository\EventRepository;
use GlavPro\CrmStages\Service\EventService;
use PHPUnit\Framework\TestCase;

/**
 * Property tests for EventService: completeness, ordering, filtering.
 */
class EventServicePropertiesTest extends TestCase
{
    use TestTrait;

    private const EVENT_TYPES = [
        'contact_attempt', 'lpr_conversation', 'discovery_filled',
        'demo_planned', 'demo_conducted', 'invoice_created',
        'kp_sent', 'payment_received', 'certificate_issued', 'stage_transition',
    ];

    /**
     * Feature: crm-stages, Property 6: Event completeness — all events contain required fields
     * Validates: Requirements 10.2
     */
    public function testEventCompleteness(): void
    {
        $this
            ->forAll(
                Generator\choose(1, 1000),
                Generator\choose(1, 100),
                Generator\elements(...self::EVENT_TYPES),
            )
            ->then(function (int $companyId, int $managerId, string $type): void {
                $repo = new EventRepository();
                $service = new EventService($repo);

                $event = $service->recordEvent($companyId, $managerId, $type, ['test' => true]);

                $this->assertGreaterThan(0, $event->id, 'Event must have a positive id');
                $this->assertSame($companyId, $event->companyId, 'company_id must match');
                $this->assertSame($managerId, $event->managerId, 'manager_id must match');
                $this->assertSame($type, $event->type, 'type must match');
                $this->assertIsArray($event->payload, 'payload must be array');
                $this->assertNotEmpty($event->createdAt, 'created_at must not be empty');
            });
    }

    /**
     * Feature: crm-stages, Property 7: Event ordering — reverse chronological
     * Validates: Requirements 10.3
     */
    public function testEventOrdering(): void
    {
        $this
            ->forAll(Generator\choose(2, 10))
            ->then(function (int $count): void {
                $repo = new EventRepository();
                $service = new EventService($repo);
                $companyId = 1;

                for ($i = 0; $i < $count; $i++) {
                    $type = self::EVENT_TYPES[$i % count(self::EVENT_TYPES)];
                    $service->recordEvent($companyId, 1, $type, ['seq' => $i]);
                }

                $events = $service->getEvents($companyId);
                $this->assertCount($count, $events);

                for ($i = 1; $i < count($events); $i++) {
                    $this->assertGreaterThanOrEqual(
                        $events[$i]->createdAt,
                        $events[$i - 1]->createdAt,
                        'Events must be in reverse chronological order'
                    );
                }
            });
    }

    /**
     * Feature: crm-stages, Property 8: Event filtering — type match
     * Validates: Requirements 10.4
     */
    public function testEventFiltering(): void
    {
        $this
            ->forAll(Generator\elements(...self::EVENT_TYPES))
            ->then(function (string $filterType): void {
                $repo = new EventRepository();
                $service = new EventService($repo);
                $companyId = 1;

                // Record events of various types
                foreach (self::EVENT_TYPES as $type) {
                    $service->recordEvent($companyId, 1, $type, []);
                }

                $filtered = $service->getEvents($companyId, $filterType);

                foreach ($filtered as $event) {
                    $this->assertSame(
                        $filterType,
                        $event->type,
                        "Filtered events must all be of type {$filterType}"
                    );
                }

                $this->assertNotEmpty($filtered, "Should find at least one event of type {$filterType}");
            });
    }
}
