<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Document;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\SynchronizeDossierArtifactsHandler;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\LinkedWooDecisionUpdater;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\BatchDownload\BatchDownloadFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class LinkedWooDecisionUpdaterTest extends SharedWebTestCase
{
    private LinkedWooDecisionUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->updater = self::fromContainer(LinkedWooDecisionUpdater::class);
    }

    public function testSynchronizesEveryDecisionTheDocumentIsLinkedTo(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();
        $document = DocumentFactory::createOne(['dossiers' => [$decisionA, $decisionB]]);

        $this->updater->updateLinkedTo($decisionB, [$document->getId()]);

        self::assertEqualsCanonicalizing(
            [$decisionA->getId()->toRfc4122(), $decisionB->getId()->toRfc4122()],
            $this->getSynchronizedDossierIds(),
        );
    }

    public function testSynchronizesOnlyTheDecisionOfASingleDecisionDocument(): void
    {
        $decision = WooDecisionFactory::createOne();
        WooDecisionFactory::createOne();
        $document = DocumentFactory::createOne(['dossiers' => [$decision]]);

        $this->updater->updateLinkedTo($decision, [$document->getId()]);

        self::assertSame([$decision->getId()->toRfc4122()], $this->getSynchronizedDossierIds());
    }

    public function testSynchronizesEachDecisionOnlyOnceForMultipleDocuments(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();
        $documents = DocumentFactory::createMany(3, ['dossiers' => [$decisionA, $decisionB]]);

        $this->updater->updateLinkedTo(
            $decisionB,
            array_map(static fn (Document $document): Uuid => $document->getId(), $documents),
        );

        self::assertEqualsCanonicalizing(
            [$decisionA->getId()->toRfc4122(), $decisionB->getId()->toRfc4122()],
            $this->getSynchronizedDossierIds(),
        );
    }

    public function testSkipsTheGivenDecisionWhenUpdatingTheOtherLinkedDecisions(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();
        $document = DocumentFactory::createOne(['dossiers' => [$decisionA, $decisionB]]);

        $this->updater->updateOtherLinkedTo($decisionB, [$document->getId()]);

        self::assertSame([$decisionA->getId()->toRfc4122()], $this->getSynchronizedDossierIds());
    }

    public function testMarksTheBatchDownloadOfEveryLinkedDecisionAsOutdated(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();
        $document = DocumentFactory::new()->withdrawn()->create(['dossiers' => [$decisionA, $decisionB]]);

        $batchDownload = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision($decisionA),
            'expiration' => new DateTimeImmutable('+1 day'),
        ]);
        self::assertFalse($batchDownload->getStatus()->isOutdated());

        $this->updater->updateLinkedTo($decisionB, [$document->getId()]);
        $this->synchronizeDossierArtifacts();

        self::fromContainer(EntityManagerInterface::class)->refresh($batchDownload);
        self::assertTrue($batchDownload->getStatus()->isOutdated());
    }

    public function testAppliesNothingWhenOneOfTheDecisionsRejectsTheTransition(): void
    {
        $decisionA = WooDecisionFactory::createOne(['status' => DossierStatus::NEW]);
        $decisionB = WooDecisionFactory::createOne();
        $document = DocumentFactory::createOne(['dossiers' => [$decisionA, $decisionB]]);

        $this->expectException(DossierWorkflowException::class);

        try {
            $this->updater->updateLinkedTo($decisionB, [$document->getId()]);
        } finally {
            self::assertSame([], $this->getSynchronizedDossierIds());
        }
    }

    /**
     * @return list<string>
     */
    private function getSynchronizedDossierIds(): array
    {
        return array_map(
            static fn (SynchronizeDossierArtifactsCommand $command): string => $command->getUuid()->toRfc4122(),
            $this->getSynchronizeDossierArtifactsCommands(),
        );
    }

    private function synchronizeDossierArtifacts(): void
    {
        $handler = self::fromContainer(SynchronizeDossierArtifactsHandler::class);

        foreach ($this->getSynchronizeDossierArtifactsCommands() as $command) {
            $handler($command);
        }
    }

    /**
     * @return list<SynchronizeDossierArtifactsCommand>
     */
    private function getSynchronizeDossierArtifactsCommands(): array
    {
        $transport = self::getContainer()->get('messenger.transport.high');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $commands = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof SynchronizeDossierArtifactsCommand) {
                $commands[] = $message;
            }
        }

        return $commands;
    }
}
