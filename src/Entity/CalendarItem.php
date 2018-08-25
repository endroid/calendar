<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Entity;

use DateTime;
use DateInterval;

class CalendarItem
{
    private $id;
    private $title;
    private $description;
    private $dateStart;
    private $dateEnd;
    private $repeatInterval;
    private $repeatDays;
    private $repeatExceptions;
    private $repeatCount;
    private $repeatEndDate;
    private $calendar;
    private $originalDate;

    public function __construct()
    {
        $this->repeatDays = [];
        $this->repeatExceptions = [];
        $this->repeatCount = 0;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
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

    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDateStart(DateTime $dateStart): void
    {
        $this->dateStart = $dateStart;
    }

    public function getDateStart(): DateTime
    {
        return $this->dateStart;
    }

    public function setDateEnd(DateTime $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

    public function getDateEnd(): DateTime
    {
        return $this->dateEnd;
    }

    public function setRepeatInterval(DateInterval $repeatInterval = null): void
    {
        $this->repeatInterval = $repeatInterval;
    }

    public function getRepeatInterval(): ?DateInterval
    {
        return $this->repeatInterval;
    }

    public function setRepeatDays(array $repeatDays): void
    {
        $this->repeatDays = $repeatDays;
    }

    public function getRepeatDays(): array
    {
        return (array) $this->repeatDays;
    }

    public function setRepeatExceptions(array $repeatExceptions): void
    {
        $this->repeatExceptions = $repeatExceptions;
    }

    public function addRepeatException(DateTime $repeatException): void
    {
        $this->repeatExceptions[] = $repeatException;
    }

    public function isRepeatException(DateTime $date): bool
    {
        foreach ($this->repeatExceptions as $repeatException) {
            if ($date == $repeatException) {
                return true;
            }
        }

        return false;
    }

    public function getRepeatExceptions(): array
    {
        return $this->repeatExceptions;
    }

    public function setRepeatCount($repeatCount): void
    {
        $this->repeatCount = $repeatCount;
    }

    public function getRepeatCount(): int
    {
        return $this->repeatCount;
    }

    public function setRepeatEndDate(DateTime $repeatEndDate = null): void
    {
        $this->repeatEndDate = $repeatEndDate;
    }

    public function getRepeatEndDate(): DateTime
    {
        return $this->repeatEndDate;
    }

    public function setCalendar(Calendar $calendar): void
    {
        $this->calendar = $calendar;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function setOriginalDate(DateTime $originalDate): void
    {
        $this->originalDate = $originalDate;
    }

    public function getOriginalDate(): ?DateTime
    {
        return $this->originalDate;
    }

    public function getEvents(DateTime $dateStart = null, DateTime $dateEnd = null): array
    {
        $events = [];

        if (null === $dateStart) {
            $dateStart = new DateTime();
        }

        if (null === $dateEnd) {
            $dateEnd = clone $dateStart;
            $dateEnd->add(new DateInterval('P1Y'));
        }

        if ((null !== $this->repeatEndDate) && ($this->repeatEndDate < $dateEnd)) {
            $dateEnd = $this->repeatEndDate;
        }

        /** @var DateTime[][] $repeatDates */
        $repeatDates = $this->getRepeatDates();

        for ($count = 0; true; ++$count) {
            if ($this->repeatCount > 0 && $count >= $this->repeatCount) {
                break;
            }
            foreach ($repeatDates as $repeatDate) {
                if ($repeatDate['start'] <= $dateEnd && $repeatDate['end'] >= $dateStart && !$this->isRepeatException($repeatDate['start'])) {
                    $events[] = $this->createEvent(clone $repeatDate['start'], clone $repeatDate['end']);
                }
                if (!$this->repeatInterval || $repeatDate['start'] > $dateEnd) {
                    break 2;
                }
                $repeatDate['start']->add($this->repeatInterval);
                $repeatDate['end']->add($this->repeatInterval);
            }
        }

        return $events;
    }

    public function getRepeatDates(): array
    {
        $repeatDateStart = clone $this->getDateStart();
        $repeatDateEnd = clone $this->getDateEnd();
        $repeatDates = [['start' => clone $repeatDateStart, 'end' => clone $repeatDateEnd]];
        $repeatDays = $this->getRepeatDays();

        $dayInterval = new DateInterval('P1D');
        for ($i = 0; $i < 6; ++$i) {
            $repeatDateStart->add($dayInterval);
            $repeatDateEnd->add($dayInterval);
            if (in_array($repeatDateStart->format('w'), $repeatDays)) {
                $repeatDates[] = ['start' => clone $repeatDateStart, 'end' => clone $repeatDateEnd];
            }
        }

        return $repeatDates;
    }

    protected function createEvent(DateTime $dateStart, DateTime $dateEnd): Event
    {
        $event = new Event();
        $event->setTitle($this->title);
        $event->setDescription($this->description);
        $event->setDateStart($dateStart);
        $event->setDateEnd($dateEnd);

        return $event;
    }
}
