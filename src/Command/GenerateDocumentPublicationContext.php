<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function sprintf;
use function str_ends_with;
use function strlen;
use function substr;

#[AsCommand(name: self::COMMAND_NAME, description: 'Generate the publicationContext of documents that do not have one yet')]
class GenerateDocumentPublicationContext extends Command
{
    public const string COMMAND_NAME = 'woopie:document:generate-document-publication-context';

    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentRepository $documentRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $result = $this->process($io, $dryRun);

        if ($dryRun) {
            $io->warning('THIS IS A DRY RUN, NOTHING WAS UPDATED IN THE DATABASE');
        } else {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Finished: processed %d, updated %d, skipped %d', $result['processed'], $result['updated'], $result['skipped']));

        return self::SUCCESS;
    }

    /**
     * @return array{processed: int, updated: int, skipped: int}
     */
    private function process(SymfonyStyle $io, bool $dryRun): array
    {
        $processed = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->documentRepository->getDocumentsMissingPublicationContextIterable() as $document) {
            Assert::isInstanceOf($document, Document::class);
            $processed++;

            $publicationContext = $this->getPublicationContext($io, $document);
            if ($publicationContext === null) {
                $skipped++;
            } else {
                $document->setPublicationContext($publicationContext);
                $updated++;
            }

            if ($processed % self::BATCH_SIZE === 0) {
                if (! $dryRun) {
                    $this->entityManager->flush();
                }
                $this->entityManager->clear();
            }
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function getPublicationContext(SymfonyStyle $io, Document $document): ?PublicationContext
    {
        $documentNumber = $document->getDocumentNumber();
        $suffix = sprintf('-%s', $document->getDocumentId()->toString());

        if (! str_ends_with($documentNumber, $suffix)) {
            return null;
        }

        try {
            return PublicationContext::fromString(substr($documentNumber, 0, -strlen($suffix)));
        } catch (InvalidArgumentException) {
            $io->warning(sprintf('PublicationContext failed for documentNumber "%s" (id: "%s")', $documentNumber, $document->getId()));

            return null;
        }
    }
}
