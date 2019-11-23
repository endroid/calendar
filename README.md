# Calendar

*By [endroid](https://endroid.nl/)*

[![Latest Stable Version](http://img.shields.io/packagist/v/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)
[![Build Status](https://github.com/endroid/calendar/workflows/CI/badge.svg)](https://github.com/endroid/calendar/actions)
[![Total Downloads](http://img.shields.io/packagist/dt/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)
[![Total Downloads](http://img.shields.io/packagist/dm/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)
[![License](http://img.shields.io/packagist/l/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)

This library helps reading and writing calendars from and to different formats. To this
end each reader converts the source to a generic calendar representation which can in
turn be written to any available calendar format using one of the available writers.

Note: at this moment only read from Google Calendar is available.

## Installation

Use [Composer](https://getcomposer.org/) to install the library.

``` bash
$ composer require endroid/calendar
```

## Usage

```php
<?php

use Endroid\Calendar\Reader\IcalReader;

$url = '...';

$reader = new IcalReader();
$calendar = $reader->readFromUrl($url);

$dateStart = new \DateTime('2016-01-01');
$dateEnd = new \DateTime('2016-12-31');

$events = $calendar->getEvents($dateStart, $dateEnd);
```

## Versioning

Version numbers follow the MAJOR.MINOR.PATCH scheme. Backwards compatibility
breaking changes will be kept to a minimum but be aware that these can occur.
Lock your dependencies for production and test your code when upgrading.

## License

This bundle is under the MIT license. For the full copyright and license
information please view the LICENSE file that was distributed with this source code.
