<?php

declare(strict_types=1);

namespace Endroid\Calendar\Tests\Reader\CalendarReaderTest;

use Endroid\Calendar\Reader\IcalReader;
use PHPUnit\Framework\TestCase;

final class IcalReaderTest extends TestCase
{
    /**
     * @testdox Recurring events count is correct
     */
    public function testRecurringEventsCount()
    {
        $reader = new IcalReader();

        /**
         * Dataset characteristics
         * - item repeats 10 days in a row, starting from 13-01-2016
         * - item on 14-01 was moved to 15-01 with changed time
         * - item on 17-01 was removed.
         */
        $calendar = $reader->readFromPath(__DIR__.'/../test.ics');

        $startDate = new \DateTimeImmutable('2016-01-01 00:00');
        $endDate = new \DateTimeImmutable('2016-02-01 00:00');

        $events = $calendar->getEvents($startDate, $endDate);

        $this->assertCount(9, $events);
    }
}
