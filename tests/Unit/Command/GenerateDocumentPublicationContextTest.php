<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Shared\Command\GenerateDocumentPublicationContext;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

use function sprintf;

class GenerateDocumentPublicationContextTest extends UnitTestCase
{
    public function testExecuteGeneratesDistillableDocuments(): void
    {
        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')
            ->andReturn($documentId);
        $document->expects('getDocumentNumber')
            ->andReturn(sprintf('%s-%s', $publicationContext->toString(), $documentId->toString()));
        $document->expects('setPublicationContext')
            ->with(Mockery::on(static function (PublicationContext $actualPublicationContext) use ($publicationContext): bool {
                return $actualPublicationContext->toString() === $publicationContext->toString();
            }));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $commandTester = $this->executeCommand($entityManager, $documentRepository, false);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 1, skipped 0', $commandTester->getDisplay());
    }

    public function testExecuteSkipsDocumentsWhoseNumberDoesNotEndWithTheDocumentId(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')
            ->andReturn($this->getFaker()->documentId());
        $document->expects('getDocumentNumber')
            ->andReturn($this->getFaker()->word());

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $commandTester = $this->executeCommand($entityManager, $documentRepository, false);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 0, skipped 1', $commandTester->getDisplay());
    }

    public function testExecuteSkipsDocumentsThatProduceAnInvalidPublicationContext(): void
    {
        $documentId = $this->getFaker()->documentId();

        $document = Mockery::mock(Document::class);
        $document->expects('getId')
            ->andReturn(Uuid::fromString($this->getFaker()->uuid()));
        $document->expects('getDocumentId')
            ->andReturn($documentId);
        $document->expects('getDocumentNumber')
            ->andReturn(sprintf('-%s', $documentId->toString()));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $commandTester = $this->executeCommand($entityManager, $documentRepository, false);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 0, skipped 1', $commandTester->getDisplay());
    }

    public function testExecuteWithDryRunDoesNotFlush(): void
    {
        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')
            ->andReturn($documentId);
        $document->expects('getDocumentNumber')
            ->andReturn(sprintf('%s-%s', $publicationContext->toString(), $documentId->toString()));
        $document->expects('setPublicationContext')
            ->with(Mockery::on(static function (PublicationContext $actualPublicationContext) use ($publicationContext): bool {
                return $actualPublicationContext->toString() === $publicationContext->toString();
            }));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);

        $commandTester = $this->executeCommand($entityManager, $documentRepository, true);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 1, skipped 0', $commandTester->getDisplay());
    }

    private function executeCommand(
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        bool $dryRun,
    ): CommandTester {
        $command = new GenerateDocumentPublicationContext($entityManager, $documentRepository);

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute($dryRun ? ['--dry-run' => true] : []);

        return $commandTester;
    }

    public function testExecuteDisplaysAWarningWhenAnInvalidPublicationContextIsProduced(): void
    {
        $id = Uuid::fromString($this->getFaker()->uuid());
        $documentId = $this->getFaker()->documentId();
        $documentNumber = sprintf('-%s', $documentId->toString());

        $document = Mockery::mock(Document::class);
        $document->expects('getId')
            ->andReturn($id);
        $document->expects('getDocumentId')
            ->andReturn($documentId);
        $document->expects('getDocumentNumber')
            ->andReturn($documentNumber);

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $commandTester = $this->executeCommand($entityManager, $documentRepository, false);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('PublicationContext failed for documentNumber', $commandTester->getDisplay());
    }
}
