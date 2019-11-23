<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Reader;

use Endroid\Calendar\Entity\Calendar;
use Endroid\Calendar\Entity\CalendarItem;

class IcalReader
{
    private $weekDays = [
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6,
        'SU' => 7,
    ];

    public function readFromUrl(string $url): Calendar
    {
        $calendarData = (string) file_get_contents($url);

        return $this->readFromString($calendarData);
    }

    public function readFromFile(string $fileName): Calendar
    {
        $calendarData = (string) file_get_contents($fileName);

        return $this->readFromString($calendarData);
    }

    public function readFromString(string $calendarData): Calendar
    {
        $calendar = $this->parseCalendarData($calendarData);

        return $calendar;
    }

    public function parseCalendarData(string $calendarData): Calendar
    {
        $calendar = new Calendar();
        $calendar->setTitle(strval($this->getValue('X-WR-CALNAME', $calendarData)));

        preg_match_all('#BEGIN:VEVENT.*?END:VEVENT#s', $calendarData, $matches);

        $calendarItemDataArray = $matches[0];
        foreach ($calendarItemDataArray as $calendarItemData) {
            $this->parseCalendarItemData($calendarItemData, $calendar);
        }

        $this->processRevisions($calendar->getCalendarItems());

        return $calendar;
    }

    private function parseCalendarItemData(string $calendarItemData, Calendar $calendar): CalendarItem
    {
        $calendarItem = new CalendarItem($calendar);
        $calendarItem->setId(strval($this->getValue('UID', $calendarItemData)));
        $calendarItem->setTitle(strval($this->getValue('SUMMARY', $calendarItemData)));
        $calendarItem->setDescription($this->getValue('DESCRIPTION', $calendarItemData));
        $calendarItem->setDateStart($this->getDate('DTSTART', $calendarItemData));
        $calendarItem->setDateEnd($this->getDate('DTEND', $calendarItemData));

        $this->setRepeatRule($calendarItemData, $calendarItem);
        $this->setOriginalDate($calendarItemData, $calendarItem);

        return $calendarItem;
    }

    private function setRepeatRule(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = $this->getData('RRULE', $calendarItemData);

        if (0 == count($data)) {
            return;
        }

        $calendarItem->setRepeatInterval($this->getRepeatInterval($data[0]));
        $calendarItem->setRepeatDays($this->getRepeatDays($data[0]));
        $calendarItem->setRepeatCount($this->getRepeatCount($data[0]));
        $calendarItem->setRepeatEndDate($this->getRepeatEndDate($data[0]));
        $this->setRepeatExceptions($calendarItemData, $calendarItem);
    }

    private function setRepeatExceptions(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = $this->getData('EXDATE', $calendarItemData);

        foreach ($data as $line) {
            $date = $this->createDate($line);
            $calendarItem->addRepeatException($date);
        }
    }

    private function setOriginalDate(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = $this->getData('RECURRENCE-ID', $calendarItemData);

        if (0 == count($data)) {
            return;
        }

        $date = $this->createDate($data[0]);
        $calendarItem->setOriginalDate($date);
    }

    private function getData(string $name, string $calendarData): array
    {
        $data = [];

        $pattern = '#('.preg_quote($name, '#').'([^:]*)):([^\\r\\n]*)#';
        preg_match_all($pattern, $calendarData, $matches);

        for ($i = 0; $i < count($matches[0]); ++$i) {
            $line = [];
            $values = array_merge(explode(';', trim($matches[2][$i], ';')), explode(';', $matches[3][$i]));
            foreach ($values as $value) {
                if (false !== strpos($value, '=')) {
                    $parts = explode('=', $value);
                    $line['extra'][$parts[0]] = $parts[1];
                } else {
                    $line['value'] = $value;
                }
            }
            $data[] = $line;
        }

        return $data;
    }

    private function getValue(string $name, string $calendarData): ?string
    {
        $data = $this->getData($name, $calendarData);

        return count($data) ? $data[0]['value'] : null;
    }

    private function getDate(string $name, string $calendarData): \DateTime
    {
        $data = $this->getData($name, $calendarData);
        $date = $this->createDate($data[0]);

        return $date;
    }

    private function getRepeatInterval(array $data): ?\DateInterval
    {
        if (!isset($data['extra']['FREQ'])) {
            return null;
        }

        $frequency = substr($data['extra']['FREQ'], 0, 1);
        $interval = isset($data['extra']['INTERVAL']) ? $data['extra']['INTERVAL'] : 1;

        if ('W' == $frequency) {
            $frequency = 'D';
            $interval *= 7;
        }

        $dateInterval = new \DateInterval('P'.$interval.$frequency);

        return $dateInterval;
    }

    private function getRepeatDays(array $data): array
    {
        if (!isset($data['extra']['BYDAY'])) {
            return [];
        }

        $days = explode(',', $data['extra']['BYDAY']);
        foreach ($days as &$day) {
            $day = $this->weekDays[$day];
        }

        return $days;
    }

    private function getRepeatCount(array $data): int
    {
        if (!isset($data['extra']['COUNT'])) {
            return 0;
        }

        $repeatCount = (int) $data['extra']['COUNT'];

        return $repeatCount;
    }

    private function getRepeatEndDate(array $data): ?\DateTime
    {
        if (!isset($data['extra']['UNTIL'])) {
            return null;
        }

        $repeatEndDate = new \DateTime($data['extra']['UNTIL']);

        return $repeatEndDate;
    }

    private function createDate(array $data): \DateTime
    {
        $zone = new \DateTimeZone(isset($data['extra']['TZID']) ? $data['extra']['TZID'] : 'UTC');
        $date = new \DateTime($data['value'], $zone);

        return $date;
    }

    /**
     * Process revisions. Adds revisions as exceptions to
     * the original calendar item.
     */
    private function processRevisions(array $calendarItems): void
    {
        /** @var \DateTime[][] $revisedDates */
        $revisedDates = [];

        /** @var CalendarItem[] $originalCalendarItems */
        $originalCalendarItems = [];

        foreach ($calendarItems as $calendarItem) {
            if ($calendarItem->getOriginalDate()) {
                $revisedDates[$calendarItem->getId()][] = $calendarItem->getOriginalDate();
            } else {
                $originalCalendarItems[$calendarItem->getId()] = $calendarItem;
            }
        }

        foreach ($originalCalendarItems as $calendarItem) {
            if (isset($revisedDates[$calendarItem->getId()])) {
                foreach ($revisedDates[$calendarItem->getId()] as $date) {
                    $calendarItem->addRepeatException($date);
                }
            }
        }
    }
}
