<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Shared\Command\NormalizeDocumentGrounds;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

use function count;

final class NormalizeDocumentGroundsTest extends SharedWebTestCase
{
    private const string MAPPING_FILE = __DIR__ . '/fixtures/grounds_mapping_minbuza.xlsx';

    /**
     * @var array<string, list<string>>
     */
    private const array GROUND_MAPPING = [
        '5.1.2a, 5.1.2f' => ['5.1.2a', '5.1.2f'],
        '5.1.1c, 5.1.2a, 5.1.2e' => ['5.1.1c', '5.1.2a', '5.1.2e'],
        '5.1.1c, 5.1.2e, buiten verzoek' => ['5.1.1c', '5.1.2e', 'buiten verzoek'],
        '5.1.2a, 5.1.2e, 5.1.2i, buiten verzoek' => ['5.1.2a', '5.1.2e', '5.1.2i', 'buiten verzoek'],
        '5.1.2a, 5.1.2e, 5.1.2i, 5.2.1' => ['5.1.2a', '5.1.2e', '5.1.2i', '5.2.1'],
        '5.1.2e, buiten verzoek' => ['5.1.2e', 'buiten verzoek'],
        '5.1.1c, 5.1.2e, 5.1.2f' => ['5.1.1c', '5.1.2e', '5.1.2f'],
        '5.1.2e, 5.1.2f, buiten verzoek' => ['5.1.2e', '5.1.2f', 'buiten verzoek'],
        '5.1.2e, 5.1.2i' => ['5.1.2e', '5.1.2i'],
        '5.1.2e, 5.1.2f' => ['5.1.2e', '5.1.2f'],
        '5.1.2a, 5.1.2e, 5.1.2f, buiten verzoek' => ['5.1.2a', '5.1.2e', '5.1.2f', 'buiten verzoek'],
        '5.1.2a, 5.1.2e, 5.1.2i' => ['5.1.2a', '5.1.2e', '5.1.2i'],
        '5.1.2a, 5.1.2e, buiten verzoek' => ['5.1.2a', '5.1.2e', 'buiten verzoek'],
        '5.1.2a, 5.1.2e' => ['5.1.2a', '5.1.2e'],
        '5.1.2a, 5.1.2e, 5.1.2f' => ['5.1.2a', '5.1.2e', '5.1.2f'],
        '5.1.1c, 5.1.2a, 5.1.2e, 5.1.2f' => ['5.1.1c', '5.1.2a', '5.1.2e', '5.1.2f'],
    ];

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = self::fromContainer(EntityManagerInterface::class);
    }

    public function testExecuteSplitsCommaSeparatedGroundsFromMappingAndReindexesChangedDocuments(): void
    {
        $documentIds = [];
        foreach (self::GROUND_MAPPING as $sourceGround => $expectedGrounds) {
            $documentIds[$sourceGround] = DocumentFactory::createOne(['grounds' => [$sourceGround]])->getId();
        }
        $unchangedDocumentId = DocumentFactory::createOne(['grounds' => ['unmapped ground']])->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestMetadataOnlyCommandForEntity')
            ->times(count(self::GROUND_MAPPING))
            ->with(Mockery::type(Document::class), false);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        foreach ($documentIds as $sourceGround => $documentId) {
            $document = $this->entityManager->find(Document::class, $documentId);

            self::assertInstanceOf(Document::class, $document);
            self::assertSame(self::GROUND_MAPPING[$sourceGround], $document->getGrounds());
        }

        $unchangedDocument = $this->entityManager->find(Document::class, $unchangedDocumentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(['unmapped ground'], $unchangedDocument->getGrounds());
    }

    public function testReindexCanBeDisabled(): void
    {
        $document = DocumentFactory::createOne(['grounds' => ['5.1.2a, 5.1.2f']]);
        $documentId = $document->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand(['--no-dispatch-reindex' => true]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $this->entityManager->clear();

        $normalizedDocument = $this->entityManager->find(Document::class, $documentId);
        self::assertInstanceOf(Document::class, $normalizedDocument);
        self::assertSame(['5.1.2a', '5.1.2f'], $normalizedDocument->getGrounds());
    }

    public function testDryRunDoesNotDispatchReindex(): void
    {
        $document = DocumentFactory::createOne(['grounds' => ['5.1.2a, 5.1.2f']]);
        $documentId = $document->getId();
        $this->entityManager->flush();

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        self::getContainer()->set(IngestDispatcher::class, $ingestDispatcher);

        $commandTester = $this->executeCommand([
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('DRY RUN: nothing was updated in the database', $commandTester->getDisplay());
        self::assertFalse($this->entityManager->contains($document));

        $this->entityManager->clear();

        $unchangedDocument = $this->entityManager->find(Document::class, $documentId);
        self::assertInstanceOf(Document::class, $unchangedDocument);
        self::assertSame(['5.1.2a, 5.1.2f'], $unchangedDocument->getGrounds());
    }

    /**
     * @param array<string, bool> $options
     */
    private function executeCommand(array $options = []): CommandTester
    {
        $kernel = self::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $commandTester = new CommandTester($application->find(NormalizeDocumentGrounds::COMMAND_NAME));
        $commandTester->execute(
            ['mapping' => self::MAPPING_FILE, ...$options],
            ['interactive' => false],
        );

        return $commandTester;
    }
}
