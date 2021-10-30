<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Model;

class Calendar
{
    private string $title;

    /** @var array<CalendarItem> */
    private array $calendarItems = [];

    /** @param array<CalendarItem> $calendarItems */
    public function __construct(string $title, array $calendarItems = [])
    {
        $this->title = $title;
        $this->calendarItems = $calendarItems;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return array<CalendarItem> */
    public function getCalendarItems(): array
    {
        return $this->calendarItems;
    }

    /** @return array<Event> */
    public function getEvents(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        $events = [];
        foreach ($this->calendarItems as $calendarItem) {
            $events = array_merge($events, $calendarItem->getEvents($dateStart, $dateEnd));
        }

        usort($events, function (Event $a, Event $b) {
            $diff = strcmp($a->getDateStart()->format('YmdHis'), $b->getDateStart()->format('YmdHis'));

            if (0 == $diff) {
                $diff = strcmp($a->getDateEnd()->format('YmdHis'), $b->getDateEnd()->format('YmdHis'));
            }

            return $diff;
        });

        return $events;
    }
}
