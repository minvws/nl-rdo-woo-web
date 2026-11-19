<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use Shared\Command\RecalculateFileHashes\ChangedHash;
use Shared\Command\RecalculateFileHashes\FailedFile;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\FileHashService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Webmozart\Assert\Assert;

use function array_map;
use function count;
use function sprintf;

#[AsCommand(
    name: self::COMMAND_NAME,
    description: 'Recalculates file hashes of uploaded main documents and attachments',
    help: <<<'TXT'
        Recalculates the hash of every uploaded main document and attachment and stores it when it differs.
        Files that cannot be processed are skipped and listed at the end. Use -v to list every changed hash.

        Exit codes: 0 when all files were processed, 2 when the run completed but one or more files failed,
        1 when the run was aborted.
        TXT,
)]
final class RecalculateFileHashes extends Command
{
    public const string COMMAND_NAME = 'woopie:recalculate-file-hashes';
    public const int PARTIAL_FAILURE = 2;

    private const int BATCH_SIZE = 1000;
    private const string UNREADABLE_REASON = 'Could not read file from storage';

    private int $checkedCount = 0;
    private int $updatedCount = 0;
    private int $unreadableCount = 0;
    private int $failedCount = 0;
    private bool $collectChanges = false;

    /** @var list<FailedFile> */
    private array $failedFiles = [];

    /** @var list<ChangedHash> */
    private array $changedHashes = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityStorageService $entityStorageService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show changes without updating the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $this->isDryRun($input);
        $this->collectChanges = $output->isVerbose();

        $this->recalculate($dryRun);

        if ($this->collectChanges && count($this->changedHashes) > 0) {
            $io->section($dryRun ? 'Hashes that would change' : 'Changed hashes');
            $io->table(
                ['Type', 'Id', 'Prefix', 'Old hash', 'New hash'],
                array_map(static fn (ChangedHash $changedHash): array => [
                    $changedHash->type,
                    $changedHash->id->toRfc4122(),
                    $changedHash->documentPrefix ?? '-',
                    $changedHash->oldHash ?? '-',
                    $changedHash->newHash,
                ], $this->changedHashes),
            );
        }

        if (count($this->failedFiles) > 0) {
            $io->section('Failed files');
            $io->table(
                ['Type', 'Id', 'Dossier id', 'Prefix', 'File name', 'Storage path', 'Reason'],
                array_map(static fn (FailedFile $failedFile): array => [
                    $failedFile->type,
                    $failedFile->id->toRfc4122(),
                    $failedFile->dossierId?->toRfc4122() ?? '-',
                    $failedFile->documentPrefix ?? '-',
                    $failedFile->fileName ?? '-',
                    $failedFile->storagePath ?? '-',
                    $failedFile->reason,
                ], $this->failedFiles),
            );
        }

        $io->writeln(sprintf(
            '<info>Checked %d files, %s %d hashes; %d files could not be read from storage, %d files failed with an error.</info>',
            $this->checkedCount,
            $dryRun ? 'would update' : 'updated',
            $this->updatedCount,
            $this->unreadableCount,
            $this->failedCount,
        ));

        if ($dryRun) {
            $io->writeln('<comment>Dry run: no database updates were made.</comment>');
        }

        return count($this->failedFiles) > 0 ? self::PARTIAL_FAILURE : self::SUCCESS;
    }

    private function isDryRun(InputInterface $input): bool
    {
        $dryRun = $input->getOption('dry-run');
        Assert::boolean($dryRun);

        return $dryRun;
    }

    private function recalculate(bool $dryRun): void
    {
        $mainDocuments = $this->getUploadedEntitiesOfClass(AbstractMainDocument::class);
        foreach ($mainDocuments as $mainDocument) {
            $this->processEntity($mainDocument, $dryRun);
            $this->flushBatch($dryRun);
        }

        $attachments = $this->getUploadedEntitiesOfClass(AbstractAttachment::class);
        foreach ($attachments as $attachment) {
            $this->processEntity($attachment, $dryRun);
            $this->flushBatch($dryRun);
        }

        if (! $dryRun) {
            $this->entityManager->flush();
        }

        $this->entityManager->clear();
    }

    private function processEntity(AbstractMainDocument|AbstractAttachment $entity, bool $dryRun): void
    {
        $this->checkedCount++;

        $stream = null;
        try {
            $stream = $this->entityStorageService->retrieveResourceEntityAsStream($entity);
            if (! $stream instanceof StreamInterface) {
                $this->unreadableCount++;
                $this->addFailedFile($entity, self::UNREADABLE_REASON);

                return;
            }

            $hash = FileHashService::calculatePsrStreamHash($stream);
            $oldHash = $entity->getFileInfo()->getHash();
            if ($oldHash === $hash) {
                return;
            }

            $this->updatedCount++;
            $this->addChangedHash($entity, $oldHash, $hash);

            if ($dryRun) {
                return;
            }

            $entity->getFileInfo()->setHash($hash);
        } catch (Throwable $exception) {
            $this->failedCount++;
            $this->addFailedFile($entity, $exception->getMessage());
        } finally {
            $stream?->close();
        }
    }

    private function addChangedHash(AbstractMainDocument|AbstractAttachment $entity, ?string $oldHash, string $newHash): void
    {
        if (! $this->collectChanges) {
            return;
        }

        $this->changedHashes[] = new ChangedHash(
            type: new ReflectionClass($entity)->getShortName(),
            id: $entity->getId(),
            documentPrefix: $this->findDossier($entity)?->getDocumentPrefix(),
            oldHash: $oldHash,
            newHash: $newHash,
        );
    }

    private function addFailedFile(AbstractMainDocument|AbstractAttachment $entity, string $reason): void
    {
        $dossier = $this->findDossier($entity);

        $this->failedFiles[] = new FailedFile(
            type: new ReflectionClass($entity)->getShortName(),
            id: $entity->getId(),
            dossierId: $dossier?->getId(),
            documentPrefix: $dossier?->getDocumentPrefix(),
            fileName: $entity->getFileInfo()->getName(),
            storagePath: $entity->getFileInfo()->getPath(),
            reason: $reason,
        );
    }

    private function findDossier(AbstractMainDocument|AbstractAttachment $entity): ?AbstractDossier
    {
        try {
            return $entity->getDossier();
        } catch (Throwable) {
            return null;
        }
    }

    private function flushBatch(bool $dryRun): void
    {
        if ($this->checkedCount % self::BATCH_SIZE !== 0) {
            return;
        }

        if (! $dryRun) {
            $this->entityManager->flush();
        }

        $this->entityManager->clear();
    }

    /**
     * @param class-string<AbstractMainDocument|AbstractAttachment> $entityClass
     *
     * @return iterable<AbstractMainDocument|AbstractAttachment>
     */
    private function getUploadedEntitiesOfClass(string $entityClass): iterable
    {
        $query = $this->entityManager->getRepository($entityClass)
            ->createQueryBuilder('entity')
            ->where('entity.fileInfo.uploaded = true')
            ->getQuery();

        foreach ($query->toIterable() as $entity) {
            Assert::isInstanceOf($entity, $entityClass);

            yield $entity;
        }
    }
}
