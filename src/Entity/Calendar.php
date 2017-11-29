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
    private $id;
    private $title;
    private $calendarItems;

    public function __construct()
    {
        $this->calendarItems = [];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function addCalendarItem(CalendarItem $calendarItem): void
    {
        $this->calendarItems[] = $calendarItem;

        if ($calendarItem->getCalendar() !== $this) {
            $calendarItem->setCalendar($this);
        }
    }

    public function hasCalendarItem(CalendarItem $calendarItem): bool
    {
        return in_array($calendarItem, $this->calendarItems);
    }

    public function getCalendarItems(): array
    {
        return $this->calendarItems;
    }

    public function getEvents(DateTime $dateStart = null, DateTime $dateEnd = null): array
    {
        $events = [];

        foreach ($this->calendarItems as $calendarItem) {
            $events = array_merge($events, $calendarItem->getEvents($dateStart, $dateEnd));
        }

        usort($events, [$this, 'dateCompare']);

        return $events;
    }

    private function dateCompare(Event $eventA, Event $eventB)
    {
        $dateStartA = $eventA->getDateStart()->format('YmdHis');
        $dateEndA = $eventA->getDateEnd()->format('YmdHis');
        $dateStartB = $eventB->getDateStart()->format('YmdHis');
        $dateEndB = $eventB->getDateEnd()->format('YmdHis');

        $diff = strcmp($dateStartA, $dateStartB);

        if (0 == $diff) {
            $diff = strcmp($dateEndA, $dateEndB);
        }

        return $diff;
    }
}
