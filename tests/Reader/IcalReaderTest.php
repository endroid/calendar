<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Tests\Reader\CalendarReaderTest;

use DateTime;
use Endroid\Calendar\Entity\Calendar;
use Endroid\Calendar\Reader\IcalReader;
use PHPUnit_Framework_TestCase;

class IcalReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests if the number of recurring events is correct for
     * the test calendar.
     */
    public function testRecurringEventsCount()
    {
        $calendar = $this->getCalendar();

        $startDate = new DateTime('2016-01-01 00:00');
        $endDate = new DateTime('2016-02-01 00:00');

        $events = $calendar->getEvents($startDate, $endDate);

        $this->assertCount(9, $events);
    }

    /**
     * Returns the calendar used for testing.
     * - item repeats 10 days in a row, starting from 13-01-2016
     * - item on 14-01 was moved to 15-01 with changed time
     * - item on 17-01 was removed
     *
     * @return Calendar
     */
    protected function getCalendar()
    {
        $reader = new IcalReader();
        $calendarData = file_get_contents(__DIR__.'/../test.ics');
        $calendar = $reader->readFromString($calendarData);

        return $calendar;
    }
}
