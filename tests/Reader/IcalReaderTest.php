<?php

declare(strict_types=1);

namespace Endroid\Calendar\Tests\Reader\CalendarReaderTest;

use Endroid\Calendar\Reader\IcalReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class IcalReaderTest extends TestCase
{
    #[TestDox('Recurring events count is correct')]
    public function testRecurringEventsCount()
    {
        $reader = new IcalReader();

        /**
         * Dataset characteristics
         * - item repeats 10 days in a row, starting from 13-01-2016
         * - item on 14-01 was moved to 15-01 with changed time
         * - item on 17-01 was removed.
         */
        $calendar = $reader->readFromPath(__DIR__ . '/../test.ics');

        $startDate = new \DateTimeImmutable('2016-01-01 00:00');
        $endDate = new \DateTimeImmutable('2016-02-01 00:00');

        $events = $calendar->getEvents($startDate, $endDate);

        $this->assertCount(9, $events);
    }

    public function testEmptyCalendar(): void
    {
        $calendar = new IcalReader()->readFromString('');

        self::assertSame('', $calendar->getTitle());
        self::assertSame([], $calendar->getCalendarItems());
    }

    public function testEventWithoutRecurrence(): void
    {
        $calendar = new IcalReader()->readFromString(<<<'ICAL'
            BEGIN:VEVENT
            UID:meeting
            SUMMARY:Meeting
            DTSTART;TZID=Europe/Amsterdam:20261102T100000
            DTEND;TZID=Europe/Amsterdam:20261102T110000
            END:VEVENT
            ICAL);

        $items = $calendar->getCalendarItems();
        self::assertCount(1, $items);
        $item = $items[0];
        self::assertSame('meeting', $item->getId());
        self::assertSame('2026-11-02T10:00:00+01:00', $item->getDateStart()->format('c'));
        self::assertSame('2026-11-02T11:00:00+01:00', $item->getDateEnd()->format('c'));
        self::assertNull($item->getRepeatInterval());
        self::assertNull($item->getOriginalDate());
    }

    #[DataProvider('malformedEventDates')]
    public function testMalformedEventDatesAreRejected(string $dates, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new IcalReader()->readFromString("BEGIN:VEVENT\n" . $dates . "\nEND:VEVENT");
    }

    /** @return iterable<string, array{string, string}> */
    public static function malformedEventDates(): iterable
    {
        yield 'missing start' => ['DTEND:20261102T110000Z', 'Missing calendar date "DTSTART"'];
        yield 'missing end' => ['DTSTART:20261102T100000Z', 'Missing calendar date "DTEND"'];
        yield 'empty start' => ["DTSTART:\nDTEND:20261102T110000Z", 'Empty calendar date'];
        yield 'empty end' => ["DTSTART:20261102T100000Z\nDTEND:", 'Empty calendar date'];
        yield 'empty recurrence id' => [
            "DTSTART:20261102T100000Z\nDTEND:20261102T110000Z\nRECURRENCE-ID:",
            'Empty calendar date',
        ];
        yield 'empty exception' => [
            "DTSTART:20261102T100000Z\nDTEND:20261102T110000Z\nRRULE:FREQ=DAILY\nEXDATE:",
            'Empty calendar date',
        ];
    }

    public function testWeeklyRepeatDays(): void
    {
        $calendar = new IcalReader()->readFromString(<<<'ICAL'
            BEGIN:VEVENT
            DTSTART:20261102T100000Z
            DTEND:20261102T110000Z
            RRULE:FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=3
            END:VEVENT
            ICAL);

        $item = $calendar->getCalendarItems()[0];
        self::assertSame([1, 3, 5], $item->getRepeatDays());
        self::assertSame(3, $item->getRepeatCount());
        self::assertSame(7, $item->getRepeatInterval()?->d);
    }

    public function testInvalidRepeatDayIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid repeat day "XX"');

        new IcalReader()->readFromString(<<<'ICAL'
            BEGIN:VEVENT
            DTSTART:20261102T100000Z
            DTEND:20261102T110000Z
            RRULE:FREQ=WEEKLY;BYDAY=MO,XX
            END:VEVENT
            ICAL);
    }
}
