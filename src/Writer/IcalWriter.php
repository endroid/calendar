<?php

declare(strict_types=1);

namespace Endroid\Calendar\Writer;

use Endroid\Calendar\Model\Calendar;

final readonly class IcalWriter
{
    public function writeToString(Calendar $calendar, \DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'X-WR-CALNAME:'.$calendar->getTitle(),
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
        ];

        $dateTimeZoneUTC = new \DateTimeZone('UTC');

        foreach ($calendar->getEvents($dateStart, $dateEnd) as $event) {
            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'SUMMARY:'.$event->getTitle(),
                'DESCRIPTION:'.$event->getDescription(),
            ]);

            $dateStart = $event->getDateStart()->setTimezone($dateTimeZoneUTC);
            $dateEnd = $event->getDateEnd()->setTimezone($dateTimeZoneUTC);

            if ($event->isAllDay()) {
                $lines = array_merge($lines, [
                    'DTSTART;VALUE=DATE:'.$dateStart->format('Ymd\THis\Z'),
                    'DTEND;VALUE=DATE:'.$dateEnd->format('Ymd\THis\Z'),
                ]);
            } else {
                $lines = array_merge($lines, [
                    'DTSTART:'.$dateStart->format('Ymd\THis\Z'),
                    'DTEND:'.$dateEnd->format('Ymd\THis\Z'),
                ]);
            }

            $lines = array_merge($lines, [
                'UID:'.sha1($event->getId()),
                'DTSTAMP:'.$dateStart->format('Ymd\THis\Z'),
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\n", $lines);
    }
}
