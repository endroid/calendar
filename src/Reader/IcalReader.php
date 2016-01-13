<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Reader;

use DateInterval;
use DateTime;
use DateTimeZone;
use Endroid\Calendar\Entity\Calendar;
use Endroid\Calendar\Entity\CalendarItem;
use Endroid\Calendar\Exception\InvalidUrlException;

class IcalReader
{
    /**
     * @var array
     */
    protected $weekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);

    /**
     * @param string $url
     *
     * @return Calendar
     *
     * @throws InvalidUrlException
     */
    public function readFromUrl($url)
    {
        $calendarData = @file_get_contents($url);

        if (!$calendarData) {
            throw new InvalidUrlException();
        }

        return $this->readFromString($calendarData);
    }

    /**
     * @param string $fileName
     *
     * @return Calendar
     */
    public function readFromFile($fileName)
    {
        $calendarData = file_get_contents($fileName);

        return $this->readFromString($calendarData);
    }

    /**
     * @param string $calendarData
     *
     * @return Calendar
     */
    public function readFromString($calendarData)
    {
        $calendar = $this->parseCalendarData($calendarData);

        return $calendar;
    }

    /**
     * Parses calendar data to a calendar object.
     *
     * @param string $calendarData
     *
     * @return Calendar
     */
    public function parseCalendarData($calendarData)
    {
        $calendar = new Calendar();
        $calendar->setTitle($this->getValue('X-WR-CALNAME', $calendarData));

        preg_match_all('#BEGIN:VEVENT.*?END:VEVENT#s', $calendarData, $matches);

        $calendarItemDataArray = $matches[0];
        foreach ($calendarItemDataArray as $calendarItemData) {
            $calendarItem = $this->parseCalendarItemData($calendarItemData);
            $calendar->addCalendarItem($calendarItem);
        }

        $this->processRevisions($calendar->getCalendarItems());

        return $calendar;
    }

    /**
     * Parses calendar item data to a calendar item object.
     *
     * @param string $calendarItemData
     *
     * @return CalendarItem
     */
    protected function parseCalendarItemData($calendarItemData)
    {
        $calendarItem = new CalendarItem();
        $calendarItem->setId($this->getValue('UID', $calendarItemData));
        $calendarItem->setTitle($this->getValue('SUMMARY', $calendarItemData));
        $calendarItem->setDescription($this->getValue('DESCRIPTION', $calendarItemData));
        $calendarItem->setDateStart($this->getDate('DTSTART', $calendarItemData));
        $calendarItem->setDateEnd($this->getDate('DTEND', $calendarItemData));

        $this->setRepeatRule($calendarItemData, $calendarItem);
        $this->setOriginalDate($calendarItemData, $calendarItem);

        return $calendarItem;
    }

    /**
     * Sets the calendar item repeat rules.
     *
     * @param $calendarItemData
     * @param CalendarItem $calendarItem
     */
    protected function setRepeatRule($calendarItemData, CalendarItem $calendarItem)
    {
        $data = $this->getData('RRULE', $calendarItemData);

        if (count($data) == 0) {
            return;
        }

        $calendarItem->setRepeatInterval($this->getRepeatInterval($data[0]));
        $calendarItem->setRepeatDays($this->getRepeatDays($data[0]));
        $calendarItem->setRepeatCount($this->getRepeatCount($data[0]));
        $this->setRepeatExceptions($calendarItemData, $calendarItem);
    }

    /**
     * Sets the calendar repeat exceptions.
     *
     * @param $calendarItemData
     * @param CalendarItem $calendarItem
     */
    protected function setRepeatExceptions($calendarItemData, CalendarItem $calendarItem)
    {
        $data = $this->getData('EXDATE', $calendarItemData);

        foreach ($data as $line) {
            $date = $this->createDate($line);
            $calendarItem->addRepeatException($date);
        }
    }

    /**
     * Set the original date in case of a revision.
     *
     * @param $calendarItemData
     * @param CalendarItem $calendarItem
     */
    protected function setOriginalDate($calendarItemData, CalendarItem $calendarItem)
    {
        $data = $this->getData('RECURRENCE-ID', $calendarItemData);

        if (count($data) == 0) {
            return;
        }

        $date = $this->createDate($data[0]);
        $calendarItem->setOriginalDate($date);
    }

    /**
     * Returns the parsed data for a specific key.
     *
     * @param $name
     * @param $calendarData
     *
     * @return array
     */
    protected function getData($name, $calendarData)
    {
        $data = array();

        $pattern = '#('.preg_quote($name, '#').'([^:]*)):([^\\r\\n]*)#';
        preg_match_all($pattern, $calendarData, $matches);

        for ($i = 0; $i < count($matches[0]); ++$i) {
            $line = array();
            $values = array_merge(explode(';', trim($matches[2][$i], ';')), explode(';', $matches[3][$i]));
            foreach ($values as $value) {
                if (strpos($value, '=') !== false) {
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

    /**
     * Returns a value.
     *
     * @param $name
     * @param $calendarData
     *
     * @return mixed
     */
    protected function getValue($name, $calendarData)
    {
        $data = $this->getData($name, $calendarData);

        return $data[0]['value'];
    }

    /**
     * Returns a date.
     *
     * @param $name
     * @param $calendarData
     *
     * @return DateTime
     */
    protected function getDate($name, $calendarData)
    {
        $data = $this->getData($name, $calendarData);
        $date = $this->createDate($data[0]);

        return $date;
    }

    /**
     * Returns the repeat interval.
     *
     * @param array $data
     *
     * @return DateInterval
     */
    protected function getRepeatInterval(array $data)
    {
        if (!isset($data['extra']['FREQ'])) {
            return;
        }

        $frequency = substr($data['extra']['FREQ'], 0, 1);
        $interval = isset($data['extra']['INTERVAL']) ? $data['extra']['INTERVAL'] : 1;

        if ($frequency == 'W') {
            $frequency = 'D';
            $interval *= 7;
        }

        $dateInterval = new DateInterval('P'.$interval.$frequency);

        return $dateInterval;
    }

    /**
     * Returns the days by which to repeat.
     *
     * @param array $data
     *
     * @return array
     */
    protected function getRepeatDays(array $data)
    {
        if (!isset($data['extra']['BYDAY'])) {
            return array();
        }

        $days = explode(',', $data['extra']['BYDAY']);
        foreach ($days as &$day) {
            $day = $this->weekDays[$day];
        }

        return $days;
    }

    /**
     * Returns the repeat count.
     *
     * @param array $data
     *
     * @return int
     */
    protected function getRepeatCount(array $data)
    {
        if (!isset($data['extra']['COUNT'])) {
            return 0;
        }

        $repeatCount = (int) $data['extra']['COUNT'];

        return $repeatCount;
    }

    /**
     * Creates a date.
     *
     * @param $data
     *
     * @return DateTime
     */
    protected function createDate($data)
    {
        $zone = new DateTimeZone(isset($data['extra']['TZID']) ? $data['extra']['TZID'] : 'UTC');
        $date = new DateTime($data['value'], $zone);

        return $date;
    }

    /**
     * Process revisions. Adds revisions as exceptions to
     * the original calendar item.
     *
     * @param CalendarItem[] $calendarItems
     */
    protected function processRevisions(array $calendarItems)
    {
        /** @var DateTime[] $revisedDates */
        $revisedDates = array();

        /** @var CalendarItem[] $originalCalendarItems */
        $originalCalendarItems = array();

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
