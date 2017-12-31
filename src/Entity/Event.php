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
    private $title;
    private $description;
    private $dateStart;
    private $dateEnd;

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

    public function getDateStart(DateTimeZone $timeZone = null): DateTime
    {
        if (null == $timeZone) {
            $timeZone = new DateTimeZone(date_default_timezone_get());
        }

        $this->dateStart->setTimeZone($timeZone);

        return $this->dateStart;
    }

    public function setDateEnd(DateTime $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

    public function getDateEnd(DateTimeZone $timeZone = null): DateTime
    {
        if (null == $timeZone) {
            $timeZone = new DateTimeZone(date_default_timezone_get());
        }

        $this->dateEnd->setTimeZone($timeZone);

        return $this->dateEnd;
    }

    public function isAllDay(): bool
    {
        return '00' === $this->dateStart->format('H');
    }
}
