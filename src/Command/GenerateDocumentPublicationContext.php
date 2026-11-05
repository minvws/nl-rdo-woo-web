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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function sprintf;
use function str_ends_with;
use function strlen;
use function substr;

#[AsCommand(
    name: self::COMMAND_NAME,
    description: 'Lowercase document numbers and generate the publicationContext of documents that do not have one yet',
)]
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lowercased = $this->lowercaseDatabaseFields();
        $this->entityManager->clear();

        $result = $this->process($io);
        $this->entityManager->flush();

        $io->success([
            sprintf('Lowercased %d document numbers', $lowercased['documentNumbers']),
            sprintf('Lowercased %d publication contexts', $lowercased['publicationContexts']),
            sprintf('Lowercased %d document ids', $lowercased['documentIds']),
            sprintf(
                'Finished: processed %d, updated %d, skipped %d',
                $result['processed'],
                $result['updated'],
                $result['skipped'],
            ),
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array{documentNumbers: int, publicationContexts: int, documentIds: int}
     */
    private function lowercaseDatabaseFields(): array
    {
        return [
            'documentNumbers' => $this->lowercaseDatabaseField('documentNumber'),
            'publicationContexts' => $this->lowercaseDatabaseField('publicationContext'),
            'documentIds' => $this->lowercaseDatabaseField('documentId'),
        ];
    }

    private function lowercaseDatabaseField(string $field): int
    {
        $affected = $this->entityManager->createQueryBuilder()
            ->update(Document::class, 'd')
            ->set(sprintf('d.%s', $field), sprintf('LOWER(d.%s)', $field))
            ->where(sprintf('d.%s <> LOWER(d.%s)', $field, $field))
            ->getQuery()
            ->execute();

        Assert::integer($affected);

        return $affected;
    }

    /**
     * @return array{processed: int, updated: int, skipped: int}
     */
    private function process(SymfonyStyle $io): array
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
                $this->entityManager->flush();
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
        $documentNumber = $document->getDocumentNumber()->toString();
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
