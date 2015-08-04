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
     * @param string $url
     *
     * @return Calendar
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
        $calendarItem->setDateStart($this->getValue('DTSTART', $calendarItemData));
        $calendarItem->setDateEnd($this->getValue('DTEND', $calendarItemData));
        $calendarItem->setRepeatInterval($this->getValue('RRULE', $calendarItemData));
        $calendarItem->setRepeatExceptions($this->getValues('EXDATE', $calendarItemData));

        return $calendarItem;
    }

    /**
     * Returns the value for a specific key.
     *
     * @param string $name
     * @param string $calendarData
     *
     * @return mixed
     */
    protected function getValue($name, $calendarData)
    {
        $values = $this->getValues($name, $calendarData);

        return isset($values[0]) ? $values[0] : null;
    }

    /**
     * Returns the values for a specific key.
     *
     * @param string $name
     * @param string $calendarData
     *
     * @return array
     */
    protected function getValues($name, $calendarData)
    {
        $values = array();

        $pattern = '#('.$name.'[^:]*):([^\\r\\n]*)#';
        preg_match_all($pattern, $calendarData, $matches);

        for ($i = 0; $i < count($matches[0]); ++$i) {
            $key = $matches[1][$i];
            $value = $matches[2][$i];
            switch ($name) {
                case 'DTSTART':
                case 'DTEND':
                case 'EXDATE':
                    $value = $this->convertToDate($key, $value);
                    break;
                case 'RRULE':
                    $value = $this->convertToDateInterval($value);
                    break;
            }
            $values[] = $value;
        }

        return $values;
    }

    /**
     * Converts the value to a date.
     *
     * @param string $key
     * @param string $value
     *
     * @return DateTime
     */
    protected function convertToDate($key, $value)
    {
        preg_match('#TZID=([^;]+)#', $key, $timeZone);
        $timeZone = isset($timeZone[1]) ? new DateTimeZone($timeZone[1]) : null;

        $date = new DateTime($value, $timeZone);

        return $date;
    }

    /**
     * Converts the given value to a date interval.
     *
     * @param string $value
     *
     * @return DateInterval
     */
    protected function convertToDateInterval($value)
    {
        $dateInterval = null;

        preg_match('#FREQ=([A-Z]+)#', $value, $freq);
        $freq = isset($freq[1]) ? $freq[1] : 'NONE';

        preg_match('#INTERVAL=([0-9]+)#', $value, $interval);
        $interval = isset($interval[1]) ? $interval[1] : 1;

        switch ($freq) {
            case 'DAILY':
                $dateInterval = new DateInterval('P'.$interval.'D');
                break;
            case 'WEEKLY':
                $dateInterval = new DateInterval('P'.($interval * 7).'D');
                break;
            case 'MONTHLY':
                $dateInterval = new DateInterval('P'.$interval.'M');
                break;
            case 'YEARLY':
                $dateInterval = new DateInterval('P'.$interval.'Y');
                break;
        }

        return $dateInterval;
    }
}
