<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Entity;

use DateTime;

class Calendar
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var CalendarItem[]
     */
    protected $calendarItems;

    /**
     * Creates a new instance.
     */
    public function __construct()
    {
        $this->calendarItems = array();
    }

    /**
     * Returns the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the title.
     *
     * @param $title
     *
     * @return Calendar
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Adds a calendar item.
     *
     * @param CalendarItem $calendarItem
     *
     * @return $this
     */
    public function addCalendarItem(CalendarItem $calendarItem)
    {
        $this->calendarItems[] = $calendarItem;

        if ($calendarItem->getCalendar() != $this) {
            $calendarItem->setCalendar($this);
        }

        return $this;
    }

    /**
     * Checks if the calendar has the given calendar item.
     *
     * @param CalendarItem $calendarItem
     *
     * @return bool
     */
    public function hasCalendarItem(CalendarItem $calendarItem)
    {
        return in_array($calendarItem, $this->calendarItems);
    }

    /**
     * Returns all calendar items.
     *
     * @return CalendarItem[]
     */
    public function getCalendarItems()
    {
        return $this->calendarItems;
    }

    /**
     * Returns all events that match the criteria given.
     *
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     *
     * @return Event[]
     */
    public function getEvents(DateTime $dateStart = null, DateTime $dateEnd = null)
    {
        $events = array();

        foreach ($this->calendarItems as $calendarItem) {
            $events = array_merge($events, $calendarItem->getEvents($dateStart, $dateEnd));
        }

        usort($events, array($this, 'dateCompare'));

        return $events;
    }

    /**
     * Compares two dates.
     *
     * @param Event $eventA
     * @param Event $eventB
     *
     * @return int
     */
    protected function dateCompare(Event $eventA, Event $eventB)
    {
        $dateStartA = $eventA->getDateStart()->format('YmdHis');
        $dateEndA = $eventA->getDateEnd()->format('YmdHis');
        $dateStartB = $eventB->getDateStart()->format('YmdHis');
        $dateEndB = $eventB->getDateEnd()->format('YmdHis');

        $diff = strcmp($dateStartA, $dateStartB);

        if ($diff == 0) {
            $diff = strcmp($dateEndA, $dateEndB);
        }

        return $diff;
    }
}
