<?php

declare(strict_types=1);

namespace Endroid\Calendar\Tests\Model;

use Endroid\Calendar\Model\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testIdsIdentifyEventInstances(): void
    {
        $start = new \DateTimeImmutable('2026-11-02 10:00');
        $end = new \DateTimeImmutable('2026-11-02 11:00');
        $event = new Event('Meeting', 'Description', $start, $end);
        $other = new Event('Meeting', 'Description', $start, $end);

        self::assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $event->getId());
        self::assertSame($event->getId(), $event->getId());
        self::assertNotSame($event->getId(), $other->getId());
    }
}
