<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shared\Command\NormalizeDocumentGrounds;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\Assert\Assert;

use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class NormalizeDocumentGroundsTest extends UnitTestCase
{
    private ?string $mappingFile = null;

    protected function tearDown(): void
    {
        if ($this->mappingFile !== null) {
            unlink($this->mappingFile);
        }

        parent::tearDown();
    }

    public function testExecute(): void
    {
        $changedDocument = Mockery::mock(Document::class);
        $changedDocument->expects('getGrounds')
            ->andReturn(['multiple', 'remove', 'duplicate', 'unmapped', 'multiple']);
        $changedDocument->expects('setGrounds')
            ->with(['ground-a', 'ground-b', 'unmapped'])
            ->andReturnSelf();
        $changedDocument->expects('getDocumentNumber')
            ->andReturn(DocumentNumber::fromString('dossier-1'));

        $unchangedDocument = Mockery::mock(Document::class);
        $unchangedDocument->expects('getGrounds')
            ->andReturn(['unmapped']);

        $entityManager = $this->createEntityManager([$changedDocument, $unchangedDocument]);
        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->once()
            ->with($changedDocument, false);

        $commandTester = $this->executeCommand($entityManager, $ingestDispatcher);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'dossier-1: updated [multiple;remove;duplicate;unmapped;multiple] to [ground-a;ground-b;unmapped]',
            $commandTester->getDisplay(),
        );
    }

    public function testExecuteWithDryRun(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getGrounds')
            ->andReturn(['multiple']);
        $document->expects('setGrounds')
            ->with(['ground-a', 'ground-b'])
            ->andReturnSelf();
        $document->expects('getDocumentNumber')
            ->andReturn(DocumentNumber::fromString('dossier-2'));

        $entityManager = $this->createEntityManager([$document], dryRun: true);

        $commandTester = $this->executeCommand($entityManager, Mockery::mock(IngestDispatcher::class), [
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('DRY RUN: nothing was updated in the database', $commandTester->getDisplay());
    }

    public function testExecuteWithDispatchReindexDisabled(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getGrounds')
            ->andReturn(['multiple']);
        $document->expects('setGrounds')
            ->with(['ground-a', 'ground-b'])
            ->andReturnSelf();
        $document->expects('getDocumentNumber')
            ->andReturn(DocumentNumber::fromString('dossier-3'));

        $entityManager = $this->createEntityManager([$document]);

        $commandTester = $this->executeCommand(
            $entityManager,
            Mockery::mock(IngestDispatcher::class),
            ['--no-dispatch-reindex' => true],
        );

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteThrowsWhenMappingFileIsUnreadable(): void
    {
        $command = new NormalizeDocumentGrounds(
            Mockery::mock(EntityManagerInterface::class),
            Mockery::mock(IngestDispatcher::class),
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find(NormalizeDocumentGrounds::COMMAND_NAME));

        $this->expectException(ReaderException::class);
        $commandTester->execute(['mapping' => __FILE__]);
    }

    /**
     * @param list<Document> $documents
     */
    private function createEntityManager(array $documents, bool $dryRun = false): EntityManagerInterface
    {
        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')
            ->andReturn($documents);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('select')
            ->with('d')
            ->andReturn($queryBuilder);
        $queryBuilder->expects('getQuery')
            ->andReturn($query);

        $entityRepository = Mockery::mock(EntityRepository::class);
        $entityRepository->expects('createQueryBuilder')
            ->with('d')
            ->andReturn($queryBuilder);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('getRepository')
            ->with(Document::class)
            ->andReturn($entityRepository);
        if (! $dryRun) {
            $entityManager->expects('flush');
        }
        $entityManager->expects('clear');

        return $entityManager;
    }

    /**
     * @param array<string, bool> $options
     */
    private function executeCommand(
        EntityManagerInterface $entityManager,
        IngestDispatcher $ingestDispatcher,
        array $options = [],
    ): CommandTester {
        $command = new NormalizeDocumentGrounds($entityManager, $ingestDispatcher);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find(NormalizeDocumentGrounds::COMMAND_NAME));
        $commandTester->execute(
            ['mapping' => $this->mappingFile(), ...$options],
            ['interactive' => false],
        );

        return $commandTester;
    }

    private function mappingFile(): string
    {
        if ($this->mappingFile !== null) {
            return $this->mappingFile;
        }

        $mappingFile = tempnam(sys_get_temp_dir(), 'normalize-grounds-');
        Assert::string($mappingFile);
        $this->mappingFile = $mappingFile;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Source', 'Target'],
            ['multiple', 'ground-a;ground-b'],
            ['remove', null],
            ['duplicate', 'ground-a;ground-a'],
        ]);

        try {
            new Xlsx($spreadsheet)->save($mappingFile);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return $mappingFile;
    }
}
