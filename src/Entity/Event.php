<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Entity;

use DateTime;
use DateTimeZone;

class Event
{
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
     * Sets the title.
     *
     * @param $title
     *
     * @return $this
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
     * @param DateTimeZone $timeZone
     *
     * @return DateTime
     */
    public function getDateStart(DateTimeZone $timeZone = null)
    {
        if ($timeZone == null) {
            $timeZone = new DateTimeZone(date_default_timezone_get());
        }

        $this->dateStart->setTimeZone($timeZone);

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
     * @param DateTimeZone $timeZone
     *
     * @return DateTime
     */
    public function getDateEnd(DateTimeZone $timeZone = null)
    {
        if ($timeZone == null) {
            $timeZone = new DateTimeZone(date_default_timezone_get());
        }

        $this->dateEnd->setTimeZone($timeZone);

        return $this->dateEnd;
    }
}
