<?php

declare(strict_types=1);

namespace Endroid\Calendar\Reader;

use Endroid\Calendar\Model\Calendar;
use Endroid\Calendar\Model\CalendarItem;

final readonly class IcalReader
{
    /** @var array<string, int> */
    private const array WEEK_DAYS = [
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
        return $this->readFromSource($url, 'URL');
    }

    public function readFromPath(string $path): Calendar
    {
        return $this->readFromSource($path, 'path');
    }

    private function readFromSource(string $source, string $type): Calendar
    {
        $calendarData = file_get_contents($source);

        if (!is_string($calendarData)) {
            throw new \Exception(sprintf('Could not read from %s "%s"', $type, $source));
        }

        return $this->readFromString($calendarData);
    }

    public function readFromString(string $calendarData): Calendar
    {
        return $this->parseCalendarData($calendarData);
    }

    public function parseCalendarData(string $calendarData): Calendar
    {
        $matches = null;
        preg_match_all('#BEGIN:VEVENT.*?END:VEVENT#s', $calendarData, $matches);

        $calendarItems = [];
        $calendarItemDataArray = $matches[0] ?? [];
        foreach ($calendarItemDataArray as $calendarItemData) {
            $calendarItems[] = $this->parseCalendarItemData($calendarItemData);
        }

        $this->processRevisions($calendarItems);

        return new Calendar(strval(IcalDataParser::getValue('X-WR-CALNAME', $calendarData)), $calendarItems);
    }

    private function parseCalendarItemData(string $calendarItemData): CalendarItem
    {
        $calendarItem = new CalendarItem(
            strval(IcalDataParser::getValue('UID', $calendarItemData)),
            strval(IcalDataParser::getValue('SUMMARY', $calendarItemData)),
            strval(IcalDataParser::getValue('DESCRIPTION', $calendarItemData)),
            $this->getDate('DTSTART', $calendarItemData),
            $this->getDate('DTEND', $calendarItemData),
        );

        $this->setRepeatRule($calendarItemData, $calendarItem);
        $this->setOriginalDate($calendarItemData, $calendarItem);

        $calendarItem->setRawSourceData($calendarItemData);

        return $calendarItem;
    }

    private function setRepeatRule(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = IcalDataParser::getData('RRULE', $calendarItemData);
        $firstEntry = array_first($data);

        if (null === $firstEntry) {
            return;
        }

        if ('MONTHLY' === ($this->getExtra($firstEntry)['FREQ'] ?? null)) {
            // This one is not yet implemented
            return;
        }

        $calendarItem->setRepeatInterval($this->getRepeatInterval($firstEntry));
        $calendarItem->setRepeatDays($this->getRepeatDays($firstEntry));
        $calendarItem->setRepeatCount($this->getRepeatCount($firstEntry));
        $calendarItem->setRepeatEndDate($this->getRepeatEndDate($firstEntry));
        $this->setRepeatExceptions($calendarItemData, $calendarItem);
    }

    private function setRepeatExceptions(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = IcalDataParser::getData('EXDATE', $calendarItemData);

        foreach ($data as $line) {
            $date = $this->createDate($line);
            $calendarItem->addRepeatException($date);
        }
    }

    private function setOriginalDate(string $calendarItemData, CalendarItem $calendarItem): void
    {
        $data = IcalDataParser::getData('RECURRENCE-ID', $calendarItemData);
        $firstEntry = array_first($data);

        if (null === $firstEntry) {
            return;
        }

        $date = $this->createDate($firstEntry);
        $calendarItem->setOriginalDate($date);
    }

    private function getDate(string $name, string $calendarData): \DateTimeImmutable
    {
        $data = IcalDataParser::getData($name, $calendarData);
        $firstEntry = array_first($data);

        if (null === $firstEntry) {
            throw new \InvalidArgumentException(sprintf('Missing calendar date "%s"', $name));
        }

        return $this->createDate($firstEntry);
    }

    /**
     * @param array{value?: string, extra?: array<int|string, string>} $data
     *
     * @return array<int|string, string>
     */
    private function getExtra(array $data): array
    {
        return $data['extra'] ?? [];
    }

    /** @param array{value?: string, extra?: array<int|string, string>} $data */
    private function getRepeatInterval(array $data): ?\DateInterval
    {
        $extra = $this->getExtra($data);

        if (!array_key_exists('FREQ', $extra)) {
            return null;
        }

        $frequency = substr($extra['FREQ'], offset: 0, length: 1);
        $interval = (int) ($extra['INTERVAL'] ?? 1);

        if ('W' === $frequency) {
            $frequency = 'D';
            $interval *= 7;
        }

        return new \DateInterval('P' . $interval . $frequency);
    }

    /**
     * @param array{value?: string, extra?: array<int|string, string>} $data
     *
     * @return array<int>
     */
    private function getRepeatDays(array $data): array
    {
        $extra = $this->getExtra($data);

        if (!array_key_exists('BYDAY', $extra)) {
            return [];
        }

        return array_map(
            static fn(string $day): int => self::WEEK_DAYS[$day] ?? throw new \InvalidArgumentException(sprintf(
                'Invalid repeat day "%s"',
                $day,
            )),
            explode(',', $extra['BYDAY']),
        );
    }

    /** @param array{value?: string, extra?: array<int|string, string>} $data */
    private function getRepeatCount(array $data): int
    {
        $extra = $this->getExtra($data);

        if (!array_key_exists('COUNT', $extra)) {
            return 0;
        }

        return intval($extra['COUNT']);
    }

    /** @param array{value?: string, extra?: array<int|string, string>} $data */
    private function getRepeatEndDate(array $data): ?\DateTimeImmutable
    {
        $extra = $this->getExtra($data);

        if (!array_key_exists('UNTIL', $extra)) {
            return null;
        }

        return new \DateTimeImmutable($extra['UNTIL']);
    }

    /** @param array{value?: string, extra?: array<int|string, string>} $data */
    private function createDate(array $data): \DateTimeImmutable
    {
        $value = $data['value'] ?? '';
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('Empty calendar date');
        }

        $extra = $this->getExtra($data);
        $zone = new \DateTimeZone($extra['TZID'] ?? 'UTC');

        return new \DateTimeImmutable($value, $zone);
    }

    /** @param array<CalendarItem> $calendarItems */
    private function processRevisions(array $calendarItems): void
    {
        /** @var array<string, array<\DateTimeImmutable>> $revisedDates */
        $revisedDates = [];

        /** @var array<string, CalendarItem> $originalCalendarItems */
        $originalCalendarItems = [];

        foreach ($calendarItems as $calendarItem) {
            $originalDate = $calendarItem->getOriginalDate();
            if ($originalDate instanceof \DateTimeImmutable) {
                $revisedDates[$calendarItem->getId()][] = $originalDate;
                continue;
            }

            $originalCalendarItems[$calendarItem->getId()] = $calendarItem;
        }

        foreach ($originalCalendarItems as $calendarItem) {
            foreach ($revisedDates[$calendarItem->getId()] ?? [] as $date) {
                $calendarItem->addRepeatException($date);
            }
        }
    }
}
