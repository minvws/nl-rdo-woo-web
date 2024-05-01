<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class IngestDossierHandler
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DossierIngester $ingester,
    ) {
    }

    public function __invoke(IngestDossierMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(AbstractDossier::class)->find($message->getUuid());
        if ($dossier === null) {
            throw IngestException::forCannotFindDossier($message->getUuid());
        }

        $this->ingester->ingest($dossier, $message->getRefresh());

        $this->doctrine->flush();
    }
}
