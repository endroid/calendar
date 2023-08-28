<?php

declare(strict_types=1);

namespace Endroid\Calendar\Reader;

use Endroid\Calendar\Model\Calendar;
use Endroid\Calendar\Model\CalendarItem;

final class IcalReader
{
    /** @var array<string, int> */
    private const WEEK_DAYS = [
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
        $calendarData = file_get_contents($url);

        if (!is_string($calendarData)) {
            throw new \Exception(sprintf('Could not read from URL "%s"', $url));
        }

        return $this->readFromString($calendarData);
    }

    public function readFromPath(string $path): Calendar
    {
        $calendarData = file_get_contents($path);

        if (!is_string($calendarData)) {
            throw new \Exception(sprintf('Could not read from path "%s"', $path));
        }

        return $this->readFromString($calendarData);
    }

    public function readFromString(string $calendarData): Calendar
    {
        return $this->parseCalendarData($calendarData);
    }

    public function parseCalendarData(string $calendarData): Calendar
    {
        preg_match_all('#BEGIN:VEVENT.*?END:VEVENT#s', $calendarData, $matches);

        $calendarItems = [];
        $calendarItemDataArray = $matches[0];
        foreach ($calendarItemDataArray as $calendarItemData) {
            $calendarItems[] = $this->parseCalendarItemData($calendarItemData);
        }

        $this->processRevisions($calendarItems);

        return new Calendar(strval($this->getValue('X-WR-CALNAME', $calendarData)), $calendarItems);
    }

    private function parseCalendarItemData(string $calendarItemData): CalendarItem
    {
        $calendarItem = new CalendarItem(
            strval($this->getValue('UID', $calendarItemData)),
            strval($this->getValue('SUMMARY', $calendarItemData)),
            strval($this->getValue('DESCRIPTION', $calendarItemData)),
            $this->getDate('DTSTART', $calendarItemData),
            $this->getDate('DTEND', $calendarItemData)
        );

        $this->setRepeatRule($calendarItemData, $calendarItem);
        $this->setOriginalDate($calendarItemData, $calendarItem);

        $calendarItem->setRawSourceData($calendarItemData);

        return $calendarItem;
    }

    private function setRepeatRule(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = $this->getData('RRULE', $calendarItemData);

        if (0 == count($data)) {
            return;
        }

        if ('MONTHLY' === $data[0]['extra']['FREQ']) {
            // This one is not yet implemented
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

    /** @return array<mixed> */
    private function getData(string $name, string $calendarData): array
    {
        $data = [];

        $pattern = '#('.preg_quote($name, '#').'([^:]*)):([^\\r\\n]*)#';
        preg_match_all($pattern, $calendarData, $matches);

        for ($i = 0; $i < count($matches[0]); ++$i) {
            $line = [];
            $values = array_merge(explode(';', trim($matches[2][$i], ';')), explode(';', $matches[3][$i]));
            foreach ($values as $value) {
                if (str_contains($value, '=')) {
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

    private function getDate(string $name, string $calendarData): \DateTimeImmutable
    {
        $data = $this->getData($name, $calendarData);

        return $this->createDate($data[0]);
    }

    /** @param array<mixed> $data */
    private function getRepeatInterval(array $data): ?\DateInterval
    {
        if (!isset($data['extra']['FREQ'])) {
            return null;
        }

        $frequency = substr($data['extra']['FREQ'], 0, 1);
        $interval = $data['extra']['INTERVAL'] ?? 1;

        if ('W' == $frequency) {
            $frequency = 'D';
            $interval *= 7;
        }

        return new \DateInterval('P'.$interval.$frequency);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string>
     */
    private function getRepeatDays(array $data): array
    {
        if (!isset($data['extra']['BYDAY'])) {
            return [];
        }

        $days = explode(',', $data['extra']['BYDAY']);
        foreach ($days as &$day) {
            $day = self::WEEK_DAYS[$day];
        }

        return $days;
    }

    /** @param array<mixed> $data */
    private function getRepeatCount(array $data): int
    {
        if (!isset($data['extra']['COUNT'])) {
            return 0;
        }

        return intval($data['extra']['COUNT']);
    }

    /** @param array<mixed> $data */
    private function getRepeatEndDate(array $data): ?\DateTimeImmutable
    {
        if (!isset($data['extra']['UNTIL'])) {
            return null;
        }

        return new \DateTimeImmutable($data['extra']['UNTIL']);
    }

    /** @param array<mixed> $data */
    private function createDate(array $data): \DateTimeImmutable
    {
        $zone = new \DateTimeZone(isset($data['extra']['TZID']) ? $data['extra']['TZID'] : 'UTC');

        return new \DateTimeImmutable($data['value'], $zone);
    }

    /** @param array<CalendarItem> $calendarItems */
    private function processRevisions(array $calendarItems): void
    {
        /** @var array<array<\DateTimeImmutable>> $revisedDates */
        $revisedDates = [];

        /** @var array<CalendarItem> $originalCalendarItems */
        $originalCalendarItems = [];

        foreach ($calendarItems as $calendarItem) {
            $originalDate = $calendarItem->getOriginalDate();
            if ($originalDate instanceof \DateTimeImmutable) {
                $revisedDates[$calendarItem->getId()][] = $originalDate;
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
