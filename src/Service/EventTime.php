<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Event dates (startDate, endDate) are stored as the wall-clock time the admin typed, in the club's timezone,
 * not as UTC instants; 00:00 means that no time was set
 */
final class EventTime
{
    public const string TIMEZONE = 'Europe/Bratislava';

    private function __construct()
    {
    }

    /** The stored wall-clock time as a moment in the club's timezone */
    public static function local(DateTimeInterface $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d H:i:s'), new DateTimeZone(self::TIMEZONE));
    }

    /** Whether the admin set a time, not only a date */
    public static function hasTime(DateTimeInterface $date): bool
    {
        return $date->format('H:i:s') !== '00:00:00';
    }

    /** A timestamp (createdAt, modifiedAt, ...), which PHP creates and stores in UTC */
    public static function utcTimestamp(DateTimeInterface $timestamp): DateTimeImmutable
    {
        return new DateTimeImmutable($timestamp->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
    }
}
