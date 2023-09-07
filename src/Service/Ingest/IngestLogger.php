<?php

declare(strict_types=1);

namespace App\Service\Ingest;

use App\Entity\Document;
use App\Entity\IngestLog;
use App\Service\Logging\LoggingTypeInterface;
use Doctrine\ORM\EntityManagerInterface;

class IngestLogger implements LoggingTypeInterface
{
    private bool $enabled = true;
    private bool $flush = true;

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
    ) {
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isDisabled(): bool
    {
        return $this->enabled === false;
    }

    public function restore(): void
    {
        $this->enabled = true;
    }

    public function setFlush(bool $flush): void
    {
        $this->flush = $flush;
    }

    public function success(Document $document, string $event, string $message): void
    {
        $this->writeLogToDatabase($document, $event, $message, true);
    }

    public function error(Document $document, string $event, string $message): void
    {
        $this->writeLogToDatabase($document, $event, $message, false);
    }

    private function writeLogToDatabase(Document $document, string $event, string $message, bool $succes): void
    {
        if (! $this->enabled) {
            return;
        }

        $log = new IngestLog();
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setDocument($document);
        $log->setMessage($message);
        $log->setEvent($event);

        $log->setSuccess($succes);

        $this->doctrine->persist($log);

        if ($this->flush) {
            $this->doctrine->flush();
        }
    }
}
