<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Calendar\Model;

class Event
{
    /** @var string */
    private $title;

    /** @var string */
    private $description;

    /** @var \DateTimeImmutable */
    private $dateStart;

    /** @var \DateTimeImmutable */
    private $dateEnd;

    public function __construct(string $title, string $description, \DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd)
    {
        $this->title = $title;
        $this->description = $description;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
    }

    public function getUid(): string
    {
        return sha1(spl_object_hash($this));
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDateStart(\DateTimeZone $timeZone = null): \DateTimeImmutable
    {
        if (null == $timeZone) {
            $timeZone = new \DateTimeZone(date_default_timezone_get());
        }

        return $this->dateStart->setTimeZone($timeZone);
    }

    public function getDateEnd(\DateTimeZone $timeZone = null): \DateTimeImmutable
    {
        if (null == $timeZone) {
            $timeZone = new \DateTimeZone(date_default_timezone_get());
        }

        return $this->dateEnd->setTimeZone($timeZone);
    }

    public function isAllDay(): bool
    {
        return '00' === $this->dateStart->format('H');
    }
}
