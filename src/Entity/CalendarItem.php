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
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var DateTime
     */
    protected $dateStart;

    /**
     * @var DateTime
     */
    protected $dateEnd;

    /**
     * @var DateInterval
     */
    protected $repeatInterval;

    /**
     * @var DateTime[]
     */
    protected $repeatExceptions;

    /**
     * @var Calendar
     */
    protected $calendar;

    /**
     * Sets the ID.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Returns the ID.
     *
     * @return string
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
     * Sets the description.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the start date.
     *
     * @param DateTime $dateStart
     *
     * @return $this
     */
    public function setDateStart(DateTime $dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Returns the start date.
     *
     * @return DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Sets the end date.
     *
     * @param DateTime $dateEnd
     *
     * @return $this
     */
    public function setDateEnd(DateTime $dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Returns the end date.
     *
     * @return DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Sets the repeat interval.
     *
     * @param DateInterval $repeatInterval
     *
     * @return $this
     */
    public function setRepeatInterval(DateInterval $repeatInterval = null)
    {
        $this->repeatInterval = $repeatInterval;

        return $this;
    }

    /**
     * Returns the repeat interval.
     *
     * @return DateInterval
     */
    public function getRepeatInterval()
    {
        return $this->repeatInterval;
    }

    /**
     * Sets the repeat exceptions.
     *
     * @param DateTime[] $repeatExceptions
     *
     * @return $this
     */
    public function setRepeatExceptions(array $repeatExceptions)
    {
        $this->repeatExceptions = $repeatExceptions;

        return $this;
    }

    /**
     * Adds a repeat exception.
     *
     * @param DateTime $repeatException
     *
     * @return $this
     */
    public function addRepeatException(DateTime $repeatException)
    {
        $this->repeatExceptions[] = $repeatException;

        return $this;
    }

    /**
     * Checks if the given date is a repeat exception.
     *
     * @param DateTime $date
     *
     * @return bool
     */
    public function isRepeatException(DateTime $date)
    {
        foreach ($this->repeatExceptions as $repeatException) {
            if ($date == $repeatException) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all repeat exceptions.
     *
     * @return DateTime[]
     */
    public function getRepeatExceptions()
    {
        return $this->repeatExceptions;
    }

    /**
     * Sets the calendar.
     *
     * @param Calendar $calendar
     *
     * @return $this
     */
    public function setCalendar(Calendar $calendar)
    {
        $this->calendar = $calendar;

        if (!$calendar->hasCalendarItem($this)) {
            $calendar->addCalendarItem($this);
        }

        return $this;
    }

    /**
     * Returns the calendar.
     *
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
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

        if ($dateStart === null) {
            $dateStart = new DateTime();
        }

        if ($dateEnd === null) {
            $dateEnd = clone $dateStart;
            $dateEnd->add(new DateInterval('P1Y'));
        }

        $eventDateStart = clone $this->dateStart;
        $eventDateEnd = clone $this->dateEnd;

        while ($eventDateStart <= $dateEnd) {
            if ($eventDateStart <= $dateEnd && $eventDateEnd >= $dateStart && !$this->isRepeatException($eventDateStart)) {
                $events[] = $this->createEvent(clone $eventDateStart, clone $eventDateEnd);
            }
            if ($this->repeatInterval === null) {
                break;
            }
            $eventDateStart->add($this->repeatInterval);
            $eventDateEnd->add($this->repeatInterval);
        }

        return $events;
    }

    /**
     * Creates an event corresponding to this calendar item.
     *
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     *
     * @return Event
     */
    protected function createEvent(DateTime $dateStart, DateTime $dateEnd)
    {
        $event = new Event();
        $event->setTitle($this->title);
        $event->setDescription($this->description);
        $event->setDateStart($dateStart);
        $event->setDateEnd($dateEnd);

        return $event;
    }
}
