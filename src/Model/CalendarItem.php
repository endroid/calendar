<?php

declare(strict_types=1);

namespace Endroid\Calendar\Model;

final class CalendarItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $description,
        private readonly \DateTimeImmutable $dateStart,
        private readonly \DateTimeImmutable $dateEnd,
        /** @var array<string> */
        private array $repeatDays = [],
        /** @var array<\DateTimeImmutable> */
        private array $repeatExceptions = [],
        private int $repeatCount = 0,
        private ?\DateInterval $repeatInterval = null,
        private ?\DateTimeImmutable $repeatEndDate = null,
        private ?\DateTimeImmutable $originalDate = null,
        private string $rawSourceData = '',
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDateStart(): \DateTimeImmutable
    {
        return $this->dateStart;
    }

    public function getDateEnd(): \DateTimeImmutable
    {
        return $this->dateEnd;
    }

    /** @param array<string> $repeatDays */
    public function setRepeatDays(array $repeatDays): void
    {
        $this->repeatDays = $repeatDays;
    }

    /** @return array<string> */
    public function getRepeatDays(): array
    {
        return $this->repeatDays;
    }

    /** @param array<\DateTimeImmutable> $repeatExceptions */
    public function setRepeatExceptions(array $repeatExceptions): void
    {
        $this->repeatExceptions = $repeatExceptions;
    }

    public function addRepeatException(\DateTimeImmutable $repeatException): void
    {
        $this->repeatExceptions[] = $repeatException;
    }

    public function isRepeatException(\DateTimeImmutable $date): bool
    {
        foreach ($this->repeatExceptions as $repeatException) {
            if ($date == $repeatException) {
                return true;
            }
        }

        return false;
    }

    /** @return array<\DateTimeImmutable> */
    public function getRepeatExceptions(): array
    {
        return $this->repeatExceptions;
    }

    public function setRepeatCount(int $repeatCount): void
    {
        $this->repeatCount = $repeatCount;
    }

    public function getRepeatCount(): int
    {
        return $this->repeatCount;
    }

    public function setRepeatInterval(?\DateInterval $repeatInterval): void
    {
        $this->repeatInterval = $repeatInterval;
    }

    public function getRepeatInterval(): ?\DateInterval
    {
        return $this->repeatInterval;
    }

    public function setRepeatEndDate(?\DateTimeImmutable $repeatEndDate): void
    {
        $this->repeatEndDate = $repeatEndDate;
    }

    public function getRepeatEndDate(): ?\DateTimeImmutable
    {
        return $this->repeatEndDate;
    }

    public function setOriginalDate(?\DateTimeImmutable $originalDate): void
    {
        $this->originalDate = $originalDate;
    }

    public function getOriginalDate(): ?\DateTimeImmutable
    {
        return $this->originalDate;
    }

    public function setRawSourceData(string $rawSourceData): void
    {
        $this->rawSourceData = $rawSourceData;
    }

    public function getRawSourceData(): string
    {
        return $this->rawSourceData;
    }

    /** @return array<Event> */
    public function getEvents(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateEnd): array
    {
        $events = [];

        if ($this->repeatEndDate instanceof \DateTimeImmutable && $this->repeatEndDate < $dateEnd) {
            $dateEnd = $this->repeatEndDate;
        }

        $repeatDates = $this->getRepeatDates();

        for ($count = 0; true; ++$count) {
            if ($this->repeatCount > 0 && $count >= $this->repeatCount) {
                break;
            }
            foreach ($repeatDates as &$repeatDate) {
                if ($repeatDate['start'] <= $dateEnd && $repeatDate['end'] >= $dateStart && !$this->isRepeatException($repeatDate['start'])) {
                    $events[] = new Event($this->title, $this->description, $repeatDate['start'], $repeatDate['end']);
                }
                if (!$this->repeatInterval || $repeatDate['start'] > $dateEnd) {
                    break 2;
                }
                $repeatDate['start'] = $repeatDate['start']->add($this->repeatInterval);
                $repeatDate['end'] = $repeatDate['end']->add($this->repeatInterval);
            }
        }

        return $events;
    }

    /** @return array<array<\DateTimeImmutable>> */
    public function getRepeatDates(): array
    {
        $repeatDateStart = $this->getDateStart();
        $repeatDateEnd = $this->getDateEnd();
        $repeatDates = [['start' => $repeatDateStart, 'end' => $repeatDateEnd]];
        $repeatDays = $this->getRepeatDays();

        $dayInterval = new \DateInterval('P1D');
        for ($i = 0; $i < 6; ++$i) {
            $repeatDateStart = $repeatDateStart->add($dayInterval);
            $repeatDateEnd = $repeatDateEnd->add($dayInterval);
            if (in_array($repeatDateStart->format('w'), $repeatDays)) {
                $repeatDates[] = ['start' => $repeatDateStart, 'end' => $repeatDateEnd];
            }
        }

        return $repeatDates;
    }
}
