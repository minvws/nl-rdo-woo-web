<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\FileHashService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function fclose;
use function sprintf;

#[AsCommand(name: self::COMMAND_NAME, description: 'Recalculates file hashes of uploaded main documents and attachments')]
final class RecalculateFileHashes extends Command
{
    public const string COMMAND_NAME = 'woopie:recalculate-file-hashes';

    private const int BATCH_SIZE = 1000;
    private int $checkedCount = 0;
    private int $updatedCount = 0;
    private int $unreadableCount = 0;

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
        $dryRun = $this->isDryRun($input);
        $this->recalculate($dryRun);

        $output->writeln(sprintf(
            '<info>Checked %d files, %s %d hashes; %d files could not be read from storage.</info>',
            $this->checkedCount,
            $dryRun ? 'would update' : 'updated',
            $this->updatedCount,
            $this->unreadableCount,
        ));

        if ($dryRun) {
            $output->writeln('<comment>Dry run: no database updates were made.</comment>');
        }

        return self::SUCCESS;
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
            Assert::isInstanceOf($mainDocument, EntityWithFileInfo::class);
            $this->processEntity($mainDocument, $dryRun);
            $this->flushBatch($dryRun);
        }

        $attachments = $this->getUploadedEntitiesOfClass(AbstractAttachment::class);
        foreach ($attachments as $attachment) {
            Assert::isInstanceOf($attachment, EntityWithFileInfo::class);
            $this->processEntity($attachment, $dryRun);
            $this->flushBatch($dryRun);
        }

        if (! $dryRun) {
            $this->entityManager->flush();
        }

        $this->entityManager->clear();
    }

    private function processEntity(EntityWithFileInfo $entity, bool $dryRun): void
    {
        $this->checkedCount++;

        $stream = $this->entityStorageService->retrieveResourceEntity($entity);
        if ($stream === null) {
            $this->unreadableCount++;

            return;
        }

        $hash = FileHashService::calculateResourceHash($stream);
        fclose($stream);

        if ($entity->getFileInfo()->getHash() === $hash) {
            return;
        }

        $this->updatedCount++;

        if ($dryRun) {
            return;
        }

        $entity->getFileInfo()->setHash($hash);
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
     * @param class-string<EntityWithFileInfo> $entityClass
     *
     * @return iterable<mixed>
     */
    private function getUploadedEntitiesOfClass(string $entityClass): iterable
    {
        $query = $this->entityManager->getRepository($entityClass)
            ->createQueryBuilder('entity')
            ->where('entity.fileInfo.uploaded = true')
            ->getQuery();

        yield from $query->toIterable();
    }
}
