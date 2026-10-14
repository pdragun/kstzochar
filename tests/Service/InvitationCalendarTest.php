<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\EventInvitation;
use App\Entity\Location;
use App\Entity\SportType;
use App\Service\InvitationCalendar;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\String\UnicodeString;

class InvitationCalendarTest extends KernelTestCase
{
    public function testCalendarStructure(): void
    {
        $ics = $this->calendar()->single($this->invitation());

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VEVENT\r\nEND:VCALENDAR\r\n", $ics);
        $this->assertDoesNotMatchRegularExpression('/[^\r]\n/', $ics, 'every line ends with CRLF');

        $properties = self::properties($ics);
        $this->assertSame('invitation-42@kst.zochar.sk', $properties['UID']);
        $this->assertSame('20261001T081500Z', $properties['DTSTAMP']);
        $this->assertSame('CONFIRMED', $properties['STATUS']);
        $this->assertStringEndsWith('/pozvanky/2026/vylet', $properties['URL']);
        $this->assertArrayNotHasKey('LOCATION', $properties);
        $this->assertArrayNotHasKey('GEO', $properties);
        $this->assertArrayNotHasKey('X-WR-CALNAME', $properties);
    }

    public function testTextIsEscaped(): void
    {
        $invitation = $this->invitation()
            ->setTitle('Túra; Tribeč, Žibrica \\ späť')
            ->setSummary("Prvý riadok\nDruhý <b>riadok</b>");

        $properties = self::properties($this->calendar()->single($invitation));

        $this->assertSame('Túra\; Tribeč\, Žibrica \\\\ späť', $properties['SUMMARY']);
        $this->assertStringStartsWith('Prvý riadok\nDruhý riadok\n\nhttp', $properties['DESCRIPTION']);
    }

    public function testLongLinesAreFoldedWithoutBreakingCharacters(): void
    {
        $title = str_repeat('Výstup na Žibricu a Tribeč, ', 8);
        $ics = $this->calendar()->single($this->invitation()->setTitle($title));

        foreach (explode("\r\n", rtrim($ics, "\r\n")) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'a fold splits a UTF-8 character: ' . bin2hex($line));
        }
        $this->assertStringContainsString("\r\n ", $ics);
        $this->assertSame(str_replace(',', '\,', $title), self::properties($ics)['SUMMARY']);
    }

    /**
     * @return iterable<string, array{string, ?string, string, string}>
     */
    public static function dateProvider(): iterable
    {
        yield 'summer time' => ['2026-07-18 10:00', null, 'DTSTART:20260718T080000Z', 'DTEND:20260718T160000Z'];
        yield 'winter time' => ['2026-01-17 10:00', null, 'DTSTART:20260117T090000Z', 'DTEND:20260117T170000Z'];
        yield 'evening start, no end' => ['2026-07-18 19:00', null, 'DTSTART:20260718T170000Z', 'DTEND:20260718T180000Z'];
        yield 'timed end' => ['2026-07-18 07:30', '2026-07-19 16:00', 'DTSTART:20260718T053000Z', 'DTEND:20260719T140000Z'];
        yield 'end without a time lasts through the day' => ['2026-07-18 07:30', '2026-07-19 00:00', 'DTSTART:20260718T053000Z', 'DTEND:20260719T220000Z'];
        yield 'all day' => ['2026-07-18 00:00', null, 'DTSTART;VALUE=DATE:20260718', 'DTEND;VALUE=DATE:20260719'];
        yield 'all day, several days' => ['2026-07-18 00:00', '2026-07-20 00:00', 'DTSTART;VALUE=DATE:20260718', 'DTEND;VALUE=DATE:20260721'];
        yield 'all day across a year' => ['2026-12-31 00:00', '2027-01-01 00:00', 'DTSTART;VALUE=DATE:20261231', 'DTEND;VALUE=DATE:20270102'];
    }

    #[DataProvider('dateProvider')]
    public function testDates(string $start, ?string $end, string $dtStart, string $dtEnd): void
    {
        $invitation = $this->invitation()
            ->setStartDate(new DateTimeImmutable($start))
            ->setEndDate($end === null ? null : new DateTimeImmutable($end));

        $lines = explode("\r\n", $this->calendar()->single($invitation));

        $this->assertContains($dtStart, $lines);
        $this->assertContains($dtEnd, $lines);
    }

    public function testLocationAndCategories(): void
    {
        $location = new Location()
            ->setName('Tesáre, futbalové ihrisko')
            ->setAddressLocality('Tesáre')
            ->setLatitude(48.5)
            ->setLongitude(18.05);
        $invitation = $this->invitation()
            ->setLocation($location)
            ->addSportType(new SportType()->setTitle('Pešia turistika'))
            ->addSportType(new SportType()->setTitle('Cykloturistika'));

        $properties = self::properties($this->calendar()->single($invitation));
        $this->assertSame('Tesáre\, futbalové ihrisko', $properties['LOCATION']);
        $this->assertSame('48.5;18.05', $properties['GEO']);
        $this->assertSame('Pešia turistika,Cykloturistika', $properties['CATEGORIES']);

        $location->setName('Autobusová stanica')->setStreetAddress('Nádražná 1')->setPostalCode('955 01')
            ->setAddressLocality('Topoľčany')->setLatitude(null)->setLongitude(null);
        $properties = self::properties($this->calendar()->single($invitation));
        $this->assertSame('Autobusová stanica\, Nádražná 1\, 955 01 Topoľčany', $properties['LOCATION']);
        $this->assertArrayNotHasKey('GEO', $properties);
    }

    public function testFeed(): void
    {
        $second = $this->invitation()->setTitle('Druhý výlet');
        new ReflectionProperty(EventInvitation::class, 'id')->setValue($second, 43);

        $ics = $this->calendar()->feed([$this->invitation(), $second]);

        $this->assertSame(2, substr_count($ics, "BEGIN:VEVENT\r\n"));
        $this->assertStringContainsString("UID:invitation-43@kst.zochar.sk\r\n", $ics);
        $properties = self::properties($ics);
        $this->assertSame('KST Žochár Topoľčany – pozvánky', $properties['X-WR-CALNAME']);
        $this->assertSame('PT12H', $properties['REFRESH-INTERVAL;VALUE=DURATION']);
    }

    private function calendar(): InvitationCalendar
    {
        return static::getContainer()->get(InvitationCalendar::class);
    }

    private function invitation(): EventInvitation
    {
        $invitation = new EventInvitation()
            ->setTitle('Výlet')
            ->setSlug(new UnicodeString('vylet'))
            ->setSummary('Krátky výlet')
            ->setContent('<p>Výlet</p>')
            ->setStartDate(new DateTimeImmutable('2026-10-18 08:00'))
            ->setCreatedAt(new DateTimeImmutable('2026-09-30 12:00'))
            ->setModifiedAt(new DateTimeImmutable('2026-10-01 08:15'));
        new ReflectionProperty(EventInvitation::class, 'id')->setValue($invitation, 42);

        return $invitation;
    }

    /**
     * Unfolded property lines as name => value; the last one wins
     * @return array<string, string>
     */
    private static function properties(string $ics): array
    {
        $properties = [];
        foreach (explode("\r\n", str_replace("\r\n ", '', $ics)) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $properties[$name] = $value;
            }
        }

        return $properties;
    }
}
