<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Shared\Command\RecalculateFileSourceTypes;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class RecalculateFileSourceTypesTest extends SharedWebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = self::fromContainer(EntityManagerInterface::class);
    }

    public function testRecalculatesOnlyDocRecordsAndReindexesChangedDocuments(): void
    {
        $textDocument = DocumentFactory::createOne();
        $textDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $wordDocument = DocumentFactory::createOne();
        $wordDocument->getFileInfo()
            ->setMimetype('application/msword')
            ->setSourceType(SourceType::DOC);

        $otherSourceTypeDocument = DocumentFactory::createOne();
        $otherSourceTypeDocument->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::PDF);

        $textDocumentId = $textDocument->getId();
        $wordDocumentId = $wordDocument->getId();
        $otherSourceTypeDocumentId = $otherSourceTypeDocument->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->with($textDocument, false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $updatedTextDocument = $this->entityManager->find(Document::class, $textDocumentId);
        self::assertInstanceOf(Document::class, $updatedTextDocument);
        self::assertSame(SourceType::TXT, $updatedTextDocument->getFileInfo()->getSourceType());

        $unchangedWordDocument = $this->entityManager->find(Document::class, $wordDocumentId);
        self::assertInstanceOf(Document::class, $unchangedWordDocument);
        self::assertSame(SourceType::DOC, $unchangedWordDocument->getFileInfo()->getSourceType());

        $unchangedOtherSourceTypeDocument = $this->entityManager->find(Document::class, $otherSourceTypeDocumentId);
        self::assertInstanceOf(Document::class, $unchangedOtherSourceTypeDocument);
        self::assertSame(SourceType::PDF, $unchangedOtherSourceTypeDocument->getFileInfo()->getSourceType());
    }

    public function testDryRunDoesNotPersistChangesOrDispatchReindex(): void
    {
        $document = DocumentFactory::createOne();
        $document->getFileInfo()
            ->setMimetype('text/plain')
            ->setSourceType(SourceType::DOC);

        $documentId = $document->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand(dryRun: true);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $documentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(SourceType::DOC, $unchangedDocument->getFileInfo()->getSourceType());
    }

    private function executeCommand(bool $dryRun = false): CommandTester
    {
        $kernel = self::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $application = new Application($kernel);
        $application->setAutoExit(false);
        self::assertTrue($application->has(RecalculateFileSourceTypes::COMMAND_NAME));

        $commandTester = new CommandTester($application->find(RecalculateFileSourceTypes::COMMAND_NAME));
        $commandTester->execute(
            $dryRun ? ['--dry-run' => true] : [],
            ['interactive' => false],
        );

        return $commandTester;
    }
}
