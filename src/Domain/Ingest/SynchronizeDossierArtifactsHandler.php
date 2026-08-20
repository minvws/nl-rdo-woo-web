<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest;

use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Process\Dossier\DossierIngester;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
readonly class SynchronizeDossierArtifactsHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private LoggerInterface $logger,
        private DossierIngester $dossierIngester,
    ) {
    }

    public function __invoke(SynchronizeDossierArtifactsCommand $command): void
    {
        $dossier = $this->dossierRepository->find($command->getUuid());
        if (! $dossier instanceof AbstractDossier) {
            $this->logger->warning(sprintf('No dossier found in %s', self::class), [
                'uuid' => $command->getUuid(),
            ]);

            return;
        }

        $this->dossierIngester->ingest($dossier, true);
    }
}
