<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EventInvitation;
use App\Entity\Location;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Invitations as iCalendar (RFC 5545), to import into or subscribe to from a calendar app */
class InvitationCalendar
{
    /** A trip with a start time and no end date ends at this local time on the start day */
    public const string DEFAULT_END_TIME = '18:00';

    /** RFC 5545 folds content lines longer than 75 octets */
    private const int LINE_LENGTH = 75;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** A calendar with the one invitation */
    public function single(EventInvitation $invitation): string
    {
        return $this->calendar($this->event($invitation));
    }

    /**
     * A calendar to subscribe to, which clients refresh twice a day
     * @param iterable<EventInvitation> $invitations
     */
    public function feed(iterable $invitations): string
    {
        $lines = [
            'X-WR-CALNAME:' . self::escape($this->translator->trans('calendar.name')),
            'X-WR-TIMEZONE:' . EventTime::TIMEZONE,
            'REFRESH-INTERVAL;VALUE=DURATION:PT12H',
            'X-PUBLISHED-TTL:PT12H',
        ];
        foreach ($invitations as $invitation) {
            array_push($lines, ...$this->event($invitation));
        }

        return $this->calendar($lines);
    }

    /** @param list<string> $lines */
    private function calendar(array $lines): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//KST Žochár Topoľčany//kst.zochar.sk//SK',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            ...$lines,
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    /** @return list<string> */
    private function event(EventInvitation $invitation): array
    {
        $url = $this->urlGenerator->generate(
            'invitation_show_by_Year_by_Slug',
            ['year' => $invitation->getStartDate()?->format('Y'), 'slug' => $invitation->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $changed = $invitation->getModifiedAt() ?? $invitation->getCreatedAt();
        $stamp = $changed === null ? new DateTimeImmutable() : EventTime::utcTimestamp($changed);

        $lines = [
            'BEGIN:VEVENT',
            'UID:invitation-' . $invitation->getId() . '@kst.zochar.sk',
            'DTSTAMP:' . self::utc($stamp),
            'LAST-MODIFIED:' . self::utc($stamp),
            ...$this->dates($invitation),
            'SUMMARY:' . self::escape((string) $invitation->getTitle()),
            'DESCRIPTION:' . self::escape(trim(strip_tags((string) $invitation->getSummary())) . "\n\n" . $url),
            'URL:' . $url,
            'STATUS:CONFIRMED',
        ];

        $location = $invitation->getLocation();
        if ($location !== null) {
            $lines[] = 'LOCATION:' . self::escape(self::address($location));
            if ($location->getLatitude() !== null && $location->getLongitude() !== null) {
                $lines[] = 'GEO:' . $location->getLatitude() . ';' . $location->getLongitude();
            }
        }

        $categories = [];
        foreach ($invitation->getSportType() as $sportType) {
            $categories[] = self::escape($sportType->getTitle());
        }
        if ($categories !== []) {
            $lines[] = 'CATEGORIES:' . implode(',', $categories);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * DTSTART and DTEND; both must have the same value type, and a DATE end is exclusive
     * @return list<string>
     */
    private function dates(EventInvitation $invitation): array
    {
        $start = $invitation->getStartDate() ?? new DateTimeImmutable();
        $end = $invitation->getEndDate();

        // No start time: an all-day event that lasts until the end date, or one day
        if (!EventTime::hasTime($start)) {
            $lastDay = $end !== null && $end > $start ? $end : $start;

            return [
                'DTSTART;VALUE=DATE:' . $start->format('Ymd'),
                'DTEND;VALUE=DATE:' . $lastDay->modify('+1 day')->format('Ymd'),
            ];
        }

        $localStart = EventTime::local($start);
        $localEnd = match (true) {
            $end === null => $localStart->modify(self::DEFAULT_END_TIME),
            // An end without a time lasts through that day
            !EventTime::hasTime($end) => EventTime::local($end)->modify('+1 day'),
            default => EventTime::local($end),
        };
        if ($localEnd <= $localStart) {
            $localEnd = $localStart->modify('+1 hour');
        }

        return [
            'DTSTART:' . self::utc($localStart),
            'DTEND:' . self::utc($localEnd),
        ];
    }

    /** Name, street and town; the town is left out when the name already says it */
    private static function address(Location $location): string
    {
        $parts = [$location->getName(), $location->getStreetAddress()];
        if ($location->getPostalCode() !== null || mb_stripos($location->getName(), $location->getAddressLocality()) === false) {
            $parts[] = trim($location->getPostalCode() . ' ' . $location->getAddressLocality());
        }

        return implode(', ', array_filter($parts, static fn (?string $part): bool => $part !== null && $part !== ''));
    }

    private static function utc(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /** Escape a TEXT value: backslash, semicolon, comma and newlines */
    private static function escape(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\r", "\n"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $text,
        );
    }

    /** Split a line into chunks of at most 75 octets without breaking a UTF-8 character; continuations start with a space */
    private static function fold(string $line): string
    {
        $chunks = [];
        $limit = self::LINE_LENGTH;
        while (strlen($line) > $limit) {
            $chunk = mb_strcut($line, 0, $limit, 'UTF-8');
            $chunks[] = $chunk;
            $line = substr($line, strlen($chunk));
            $limit = self::LINE_LENGTH - 1;
        }
        $chunks[] = $line;

        return implode("\r\n ", $chunks);
    }
}
