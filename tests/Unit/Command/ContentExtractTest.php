<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Command\ContentExtractCommand;
use Shared\Domain\Ingest\Content\ContentExtract;
use Shared\Domain\Ingest\Content\ContentExtractCollection;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocumentRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ContentExtractTest extends UnitTestCase
{
    private Command $command;
    private ContentExtractService&MockInterface $contentExtractService;
    private EntityManagerInterface&MockInterface $entityManager;

    protected function setUp(): void
    {
        $this->contentExtractService = \Mockery::mock(ContentExtractService::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $application = new Application();
        $application->add(
            new ContentExtractCommand(
                $this->entityManager,
                $this->contentExtractService,
            )
        );

        $this->command = $application->find('woopie:dev:extract-content');
    }

    public function testExecuteHandlesEntityNotFound(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            3,
            $uuid = '1ef42b68-68d2-682a-b16e-bd5397103001',
        ]);

        $covenantDocumentRepository = \Mockery::mock(CovenantMainDocumentRepository::class);
        $covenantDocumentRepository->expects('find')->with($uuid)->andReturnNull();

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(CovenantMainDocument::class)
            ->andReturn($covenantDocumentRepository);

        $commandTester->execute([__FILE__]);

        self::assertEquals(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Could not load entity from database', $output);
    }

    public function testExecuteOutputsAllContents(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            3,
            $uuid = '1ef42b68-68d2-682a-b16e-bd5397103001',
        ]);

        $extract1 = new ContentExtract(ContentExtractorKey::TIKA, 'foo');
        $extract2 = new ContentExtract(ContentExtractorKey::TESSERACT, 'bar');
        $collection = new ContentExtractCollection();
        $collection->append($extract1);
        $collection->append($extract2);

        $covenantDocument = \Mockery::mock(CovenantMainDocument::class);
        $covenantDocumentRepository = \Mockery::mock(CovenantMainDocumentRepository::class);
        $covenantDocumentRepository->expects('find')->with($uuid)->andReturn($covenantDocument);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(CovenantMainDocument::class)
            ->andReturn($covenantDocumentRepository);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($covenantDocument, \Mockery::on(
                static fn (ContentExtractOptions $options) => count($options->getEnabledExtractors()) === count(ContentExtractorKey::cases())
            ))
            ->andReturn($collection);

        $commandTester->execute([__FILE__]);

        self::assertEquals(0, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
    }
}
