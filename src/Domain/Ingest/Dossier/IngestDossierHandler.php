<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Dossier;

use App\Domain\Ingest\IngestException;
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
        $dossier = $this->doctrine->getRepository(AbstractDossier::class)->find($message->getUuid());
        if ($dossier === null) {
            throw IngestException::forCannotFindDossier($message->getUuid());
        }

        $this->ingester->ingest($dossier, $message->getRefresh());

        $this->doctrine->flush();
    }
}
