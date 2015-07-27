Calendar
========

*By [endroid](http://endroid.nl/)*

[![Latest Stable Version](http://img.shields.io/packagist/v/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)
[![Build Status](http://img.shields.io/travis/endroid/Calendar.svg)](http://travis-ci.org/endroid/Calendar)
[![Total Downloads](http://img.shields.io/packagist/dt/endroid/calendar.svg)](https://packagist.org/packages/endroid/calendar)
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

$events = $calendar->getEvents();
```

## Symfony

You can use [`EndroidCalendarBundle`](https://github.com/endroid/EndroidCalendarBundle) to integrate this service in your Symfony application.

## Versioning

Semantic versioning ([semver](http://semver.org/)) is applied as much as possible.

## License

This bundle is under the MIT license. For the full copyright and license information, please view the LICENSE file that
was distributed with this source code.
