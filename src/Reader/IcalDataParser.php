<?php

declare(strict_types=1);

namespace Endroid\Calendar\Reader;

/** @internal */
final readonly class IcalDataParser
{
    /** @return list<array{value?: string, extra?: array<int|string, string>}> */
    public static function getData(string $name, string $calendarData): array
    {
        $data = [];

        $matches = null;
        $pattern = '#(' . preg_quote($name, delimiter: '#') . '([^:]*)):([^\\r\\n]*)#';
        preg_match_all($pattern, $calendarData, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $values = array_merge(explode(';', trim($match[2] ?? '', characters: ';')), explode(';', $match[3] ?? ''));
            $data[] = self::parseLine($values);
        }

        return $data;
    }

    /**
     * @param array<string> $values
     *
     * @return array{value?: string, extra?: array<int|string, string>}
     */
    private static function parseLine(array $values): array
    {
        $line = [];
        foreach ($values as $value) {
            $parts = explode('=', $value);
            if (array_key_exists(1, $parts)) {
                $line['extra'][$parts[0]] = $parts[1];
                continue;
            }

            $line['value'] = $value;
        }

        return $line;
    }

    public static function getValue(string $name, string $calendarData): ?string
    {
        $data = self::getData($name, $calendarData);

        return $data[0]['value'] ?? null;
    }
}
