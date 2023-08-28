<?php

declare(strict_types=1);

namespace Endroid\Calendar\Model;

final class Event
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly \DateTimeImmutable $dateStart,
        private readonly \DateTimeImmutable $dateEnd
    ) {
    }

    public function getId(): string
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
