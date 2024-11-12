<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier;

use App\Domain\Ingest\Process\IngestProcessException;
use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IngestDossierHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierIngester $ingester,
    ) {
    }

    public function __invoke(IngestDossierCommand $message): void
    {
        $dossier = $this->doctrine->getRepository(AbstractDossier::class)->find($message->uuid);
        if ($dossier === null) {
            throw IngestProcessException::forCannotFindDossier($message->uuid);
        }

        $this->ingester->ingest($dossier, $message->refresh);

        $this->doctrine->flush();
    }
}
