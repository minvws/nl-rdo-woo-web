<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\History;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\History\History;
use Shared\Domain\Publication\History\HistoryRepository;
use Shared\Service\HistoryService;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;

use function array_map;
use function array_values;

final class HistoryRepositoryTest extends SharedWebTestCase
{
    private HistoryRepository $repository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::fromContainer(HistoryRepository::class);
        $this->entityManager = self::fromContainer(EntityManagerInterface::class);
    }

    public function testPublicHistoryOfDocumentWithOnePublishedDecisionStartsAtItsPublicationDate(): void
    {
        $decision = $this->createPublishedDecision('2024-03-01');
        $document = $this->createDocument($decision);

        $this->createHistoryEntry($document, '2024-02-28', 'before');
        $this->createHistoryEntry($document, '2024-03-01', 'on');
        $this->createHistoryEntry($document, '2024-03-02', 'after');

        self::assertSame(['after', 'on'], $this->getPublicHistoryKeys($document));
    }

    public function testCutOffIsTheEarliestPublicationDateWhateverOrderTheRelationReturns(): void
    {
        $early = $this->createPublishedDecision('2024-03-01');
        $late = $this->createPublishedDecision('2024-06-01');

        foreach ([[$early, $late], [$late, $early]] as $decisions) {
            $document = $this->createDocument(...$decisions);

            $this->createHistoryEntry($document, '2024-02-28', 'before');
            $this->createHistoryEntry($document, '2024-03-02', 'between');
            $this->createHistoryEntry($document, '2024-06-02', 'after');

            self::assertSame(['after', 'between'], $this->getPublicHistoryKeys($document));
        }
    }

    public function testLinkingAConceptDecisionDoesNotChangeWhichEntriesAreVisible(): void
    {
        $document = $this->createDocument($this->createPublishedDecision('2024-03-01'));

        $this->createHistoryEntry($document, '2024-02-28', 'before');
        $this->createHistoryEntry($document, '2024-03-02', 'after');

        self::assertSame(['after'], $this->getPublicHistoryKeys($document));

        // A concept decision has a planned publication date, which must not lower the cut-off.
        $concept = WooDecisionFactory::new()->concept()->create(['publicationDate' => PlainDate::create('2020-01-01')]);
        $this->linkDecision($document, $concept);

        self::assertSame(['after'], $this->getPublicHistoryKeys($document));
    }

    public function testEntryCausedByAConceptDecisionAfterTheCutOffIsPubliclyVisible(): void
    {
        $concept = WooDecisionFactory::new()->concept()->create();
        $document = $this->createDocument($this->createPublishedDecision('2024-03-01'), $concept);

        $this->createHistoryEntry($document, '2024-03-02', 'caused-by-concept', [
            History::CONTEXT_ORIGIN_WOO_DECISION_ID => $concept->getId()->toRfc4122(),
        ]);

        self::assertSame(['caused-by-concept'], $this->getPublicHistoryKeys($document));
    }

    public function testDocumentWithOnlyConceptDecisionsHasNoPublicHistory(): void
    {
        $concept = WooDecisionFactory::new()->concept()->create(['publicationDate' => PlainDate::create('2020-01-01')]);
        $document = $this->createDocument($concept);

        $this->createHistoryEntry($document, '2021-01-01', 'ancient');
        $this->createHistoryEntry($document, '2024-03-02', 'recent');

        self::assertSame([], $this->getPublicHistoryKeys($document));
    }

    public function testPublishingASecondDecisionCanOnlyMoveTheCutOffEarlier(): void
    {
        $document = $this->createDocument($this->createPublishedDecision('2024-06-01'));

        $this->createHistoryEntry($document, '2024-03-02', 'between');
        $this->createHistoryEntry($document, '2024-06-02', 'after');

        self::assertSame(['after'], $this->getPublicHistoryKeys($document));

        $this->linkDecision($document, $this->createPublishedDecision('2024-03-01'));

        self::assertSame(['after', 'between'], $this->getPublicHistoryKeys($document));
    }

    public function testEntryWithoutAnOriginDecisionIsReturned(): void
    {
        $document = $this->createDocument($this->createPublishedDecision('2024-03-01'));

        $this->createHistoryEntry($document, '2024-03-02', 'legacy');

        self::assertSame(['legacy'], $this->getPublicHistoryKeys($document));
    }

    public function testPrivateHistoryIsNotCutOff(): void
    {
        $document = $this->createDocument(WooDecisionFactory::new()->concept()->create());

        $this->createHistoryEntry($document, '2021-01-01', 'ancient', site: HistoryService::MODE_PRIVATE);

        self::assertSame(['ancient'], $this->getHistoryKeys($document, HistoryService::MODE_PRIVATE));
    }

    private function createPublishedDecision(string $publicationDate): WooDecision
    {
        return WooDecisionFactory::new()
            ->published()
            ->create(['publicationDate' => PlainDate::create($publicationDate)]);
    }

    private function createDocument(WooDecision ...$decisions): Document
    {
        return DocumentFactory::createOne(['dossiers' => $decisions]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function createHistoryEntry(
        Document $document,
        string $createdDt,
        string $contextKey,
        array $context = [],
        string $site = HistoryService::MODE_PUBLIC,
    ): void {
        HistoryFactory::createOne([
            'type' => HistoryService::TYPE_DOCUMENT,
            'identifier' => $document->getId(),
            'createdDt' => new DateTimeImmutable($createdDt . ' 12:00:00'),
            'contextKey' => $contextKey,
            'context' => $context,
            'site' => $site,
        ]);
    }

    private function linkDecision(Document $document, WooDecision $decision): void
    {
        $managedDocument = $this->entityManager->getRepository(Document::class)->find($document->getId());
        $managedDecision = $this->entityManager->getRepository(WooDecision::class)->find($decision->getId());

        self::assertNotNull($managedDocument);
        self::assertNotNull($managedDecision);

        $managedDocument->addDossier($managedDecision);
        $this->entityManager->flush();
    }

    /**
     * @return list<string>
     */
    private function getPublicHistoryKeys(Document $document): array
    {
        return $this->getHistoryKeys($document, HistoryService::MODE_PUBLIC);
    }

    /**
     * @return list<string>
     */
    private function getHistoryKeys(Document $document, string $mode): array
    {
        // Clear the identity map so the decisions are loaded from the database, in whatever order it returns them.
        $this->entityManager->clear();

        $entries = $this->repository->getHistory(
            HistoryService::TYPE_DOCUMENT,
            $document->getId()->toRfc4122(),
            $mode,
            null,
        );

        return array_values(array_map(static fn (History $entry): string => $entry->getContextKey(), $entries));
    }
}
