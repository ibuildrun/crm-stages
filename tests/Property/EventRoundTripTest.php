<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use GlavPro\CrmStages\DTO\Event;
use PHPUnit\Framework\TestCase;

/**
 * Feature: crm-stages, Property 10: Event serialization round-trip
 * Validates: Requirements 12.5
 */
class EventRoundTripTest extends TestCase
{
    use TestTrait;

    private const EVENT_TYPES = [
        'contact_attempt', 'lpr_conversation', 'discovery_filled',
        'demo_planned', 'demo_conducted', 'invoice_created',
        'kp_sent', 'payment_received', 'certificate_issued', 'stage_transition',
    ];

    public function testEventRoundTrip(): void
    {
        $this
            ->forAll(
                Generator\choose(1, 100000),
                Generator\choose(1, 100000),
                Generator\choose(1, 1000),
                Generator\elements(...self::EVENT_TYPES),
            )
            ->withMaxSize(200)
            ->then(function (int $id, int $companyId, int $managerId, string $type): void {
                $payload = ['comment' => 'test_data', 'value' => $id];
                $now = '2025-02-10 14:00:00';

                $original = new Event(
                    id: $id,
                    companyId: $companyId,
                    managerId: $managerId,
                    type: $type,
                    payload: $payload,
                    createdAt: $now,
                );

                $array = $original->toArray();
                $restored = Event::fromArray($array);

                $this->assertSame($original->id, $restored->id);
                $this->assertSame($original->companyId, $restored->companyId);
                $this->assertSame($original->managerId, $restored->managerId);
                $this->assertSame($original->type, $restored->type);
                $this->assertSame($original->payload, $restored->payload);
                $this->assertSame($original->createdAt, $restored->createdAt);

                // Also test JSON string round-trip (simulating DB storage)
                $arrayWithJsonPayload = $array;
                $arrayWithJsonPayload['payload'] = json_encode($array['payload']);
                $restoredFromJson = Event::fromArray($arrayWithJsonPayload);
                $this->assertSame($original->payload, $restoredFromJson->payload);
            });
    }
}
