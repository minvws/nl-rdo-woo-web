<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Shared\Command\GenerateDocumentPublicationContext;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
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
            ->andReturn(DocumentNumber::fromString(sprintf('%s-%s', $publicationContext->toString(), $documentId->toString())));
        $document->expects('setPublicationContext')
            ->with(Mockery::on(static function (PublicationContext $actualPublicationContext) use ($publicationContext): bool {
                return $actualPublicationContext->toString() === $publicationContext->toString();
            }));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $query = Mockery::mock(Query::class);
        $query->expects('execute')->times(3)->andReturn(0);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('update')->with(Document::class, 'd')->times(3)->andReturnSelf();
        $queryBuilder->expects('set')->times(3)->andReturnSelf();
        $queryBuilder->expects('where')->times(3)->andReturnSelf();
        $queryBuilder->expects('getQuery')->times(3)->andReturn($query);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('createQueryBuilder')->times(3)->andReturn($queryBuilder);
        $entityManager->expects('clear');
        $entityManager->expects('flush');

        $application = new Application();
        $application->addCommand(new GenerateDocumentPublicationContext($entityManager, $documentRepository));

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 1, skipped 0', $commandTester->getDisplay());
    }

    public function testExecuteSkipsDocumentsWhoseNumberDoesNotEndWithTheDocumentId(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')
            ->andReturn($this->getFaker()->documentId());
        $document->expects('getDocumentNumber')
            ->andReturn(DocumentNumber::fromString($this->getFaker()->word()));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $query = Mockery::mock(Query::class);
        $query->expects('execute')->times(3)->andReturn(0);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('update')->with(Document::class, 'd')->times(3)->andReturnSelf();
        $queryBuilder->expects('set')->times(3)->andReturnSelf();
        $queryBuilder->expects('where')->times(3)->andReturnSelf();
        $queryBuilder->expects('getQuery')->times(3)->andReturn($query);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('createQueryBuilder')->times(3)->andReturn($queryBuilder);
        $entityManager->expects('clear');
        $entityManager->expects('flush');

        $application = new Application();
        $application->addCommand(new GenerateDocumentPublicationContext($entityManager, $documentRepository));

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute([]);

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
            ->andReturn(DocumentNumber::fromString(sprintf('-%s', $documentId->toString())));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $query = Mockery::mock(Query::class);
        $query->expects('execute')->times(3)->andReturn(0);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('update')->with(Document::class, 'd')->times(3)->andReturnSelf();
        $queryBuilder->expects('set')->times(3)->andReturnSelf();
        $queryBuilder->expects('where')->times(3)->andReturnSelf();
        $queryBuilder->expects('getQuery')->times(3)->andReturn($query);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('createQueryBuilder')->times(3)->andReturn($queryBuilder);
        $entityManager->expects('clear');
        $entityManager->expects('flush');

        $application = new Application();
        $application->addCommand(new GenerateDocumentPublicationContext($entityManager, $documentRepository));

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('processed 1, updated 0, skipped 1', $commandTester->getDisplay());
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
            ->andReturn(DocumentNumber::fromString($documentNumber));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getDocumentsMissingPublicationContextIterable')
            ->andReturn([$document]);

        $query = Mockery::mock(Query::class);
        $query->expects('execute')->times(3)->andReturn(0);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('update')->with(Document::class, 'd')->times(3)->andReturnSelf();
        $queryBuilder->expects('set')->times(3)->andReturnSelf();
        $queryBuilder->expects('where')->times(3)->andReturnSelf();
        $queryBuilder->expects('getQuery')->times(3)->andReturn($query);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('createQueryBuilder')->times(3)->andReturn($queryBuilder);
        $entityManager->expects('clear');
        $entityManager->expects('flush');

        $application = new Application();
        $application->addCommand(new GenerateDocumentPublicationContext($entityManager, $documentRepository));

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('PublicationContext failed for documentNumber', $commandTester->getDisplay());
    }
}
