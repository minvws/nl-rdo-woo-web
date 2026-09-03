<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Exception\OrganisationPrefixArgumentException;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function sprintf;

#[AsCommand(
    name: 'woopie:organisation:migrate-prefix',
    description: 'Migrate organisation prefixes from active document prefixes',
)]
final class OrganisationPrefixMigrationCommand extends Command
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepository,
        private readonly DocumentPrefixRepository $documentPrefixRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $migrated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->organisationRepository->getAllSortedByName() as $organisation) {
            if ($organisation->getPrefix() !== null) {
                $skipped++;
                continue;
            }

            $documentPrefix = $this->documentPrefixRepository
                ->getAlphabeticallyFirstByOrganisation($organisation);

            if ($documentPrefix === null) {
                $errors[] = sprintf(
                    '%s: no active document prefix found',
                    $organisation->getName(),
                );
                continue;
            }

            try {
                $organisationPrefix = OrganisationPrefix::create($documentPrefix->getPrefix());
            } catch (OrganisationPrefixArgumentException $exception) {
                $errors[] = sprintf(
                    '%s: %s',
                    $organisation->getName(),
                    $exception->getTranslationKey(),
                );
                continue;
            }

            $organisation->setPrefix($organisationPrefix);

            foreach ($organisation->getDocumentPrefixes() as $activeDocumentPrefix) {
                if ($activeDocumentPrefix !== $documentPrefix) {
                    $activeDocumentPrefix->archive();
                }
            }

            $migrated++;
        }

        $this->entityManager->flush();

        $io->text([
            sprintf('Migrated: %d', $migrated),
            sprintf('Skipped: %d', $skipped),
            sprintf('Failed: %d', count($errors)),
        ]);

        if ($errors !== []) {
            $io->error('Some organisation prefixes could not be migrated.');
            $io->listing($errors);

            return self::FAILURE;
        }

        $io->success('Organisation prefixes migrated.');

        return self::SUCCESS;
    }
}
