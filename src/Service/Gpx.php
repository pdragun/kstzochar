<?php

declare(strict_types=1);

namespace App\Service;

use phpGPX\Models\Email;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Metadata;
use phpGPX\Models\Person;
use phpGPX\phpGPX;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Gpx
{
    private function __construct(
        private readonly GpxFile $gpx,
    )
    {
    }

    public static function transform(string|GpxFile $gpx): self
    {
        if (is_string($gpx)) {
            $phpGPX = phpGPX::parse($gpx);
        } else {
            $phpGPX = $gpx;
        }

        $toSanitize = new Gpx($phpGPX);
        $toSanitize->resetMetadata();
        $toSanitize->resetAuthor();
        $toSanitize->sanitizeTrack();

        return $toSanitize;
    }

    public function toXML(): string {

        return $this->gpx->toXML()->saveXML();
    }

    private function resetMetadata(): void {
        $metadata = new Metadata();
        $metadata->description = null;
        $this->gpx->metadata = $metadata;
    }

    private function resetAuthor(): void {
        $person = new Person();
        $this->gpx->metadata->author = $person;
    }

    public function setMetaTitle(string $title): void {
        $this->gpx->metadata->name = $title;
    }

    public function setAuthor(string $author, string $emailId, string $emailDomain): void {
        $this->gpx->metadata->author->name = $author;

        $email = new Email();
        $email->id = $emailId;
        $email->domain = $emailDomain;
        $this->gpx->metadata->author->email = $email;
    }

    private function sanitizeTrack(): void {
        $this->gpx->tracks[0]->name = null;
        $this->gpx->tracks[0]->description = null;
    }

}