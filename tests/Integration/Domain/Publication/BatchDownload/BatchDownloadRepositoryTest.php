<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\BatchDownload;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadStatus;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\Publication\BatchDownload\BatchDownloadFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function abs;
use function Zenstruck\Foundry\Persistence\save;

class BatchDownloadRepositoryTest extends SharedWebTestCase
{
    private BatchDownloadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::fromContainer(BatchDownloadRepository::class);
    }

    public function testSaveAndRemove(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $download = new BatchDownload(
            scope: BatchDownloadScope::forWooDecision($wooDecision),
            expiration: new DateTimeImmutable('+1 day'),
        );

        $id = $download->getId();

        self::assertNull($this->repository->find($id));

        $this->repository->save($download);
        $result = $this->repository->find($id);
        self::assertEquals($download, $result);

        $this->repository->remove($download);
        self::assertNull($this->repository->find($id));
    }

    public function testFindExpiredArchives(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $downloadA = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision($wooDecision),
            'expiration' => new DateTimeImmutable('+1 day'),
        ]);
        $downloadB = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision($wooDecision),
            'expiration' => new DateTimeImmutable('-1 day'),
        ]);

        $result = $this->repository->findExpiredBatchDownloads();

        self::assertCount(1, $result);
        self::assertEquals($downloadB, $result[0]);
    }

    public function testGetBestAvailableBatchDownloadForScopeWithWooDecision(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();

        $dossierScope = BatchDownloadScope::forWooDecision($dossierA);
        BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);

        $failedDownload = BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);
        $failedDownload->markAsFailed();
        save($failedDownload);

        $olderDownload = BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);
        $olderDownload->complete('123.zip', 456, 789);
        save($olderDownload);

        $expectedDownload = BatchDownloadFactory::createOne([
            'expiration' => new DateTimeImmutable('+2 month'),
            'scope' => $dossierScope,
        ]);
        $expectedDownload->complete('123.zip', 456, 789);
        save($expectedDownload);

        $otherDossierDownload = BatchDownloadFactory::createOne([
            'expiration' => new DateTimeImmutable('+3 month'),
            'scope' => BatchDownloadScope::forWooDecision($dossierB),
        ]);
        $otherDossierDownload->complete('123.zip', 456, 789);
        save($otherDossierDownload);

        $inquiry = InquiryFactory::createOne();
        BatchDownloadScope::forInquiryAndWooDecision($inquiry, $dossierA);
        BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);

        self::assertEquals(
            $expectedDownload,
            $this->repository->getBestAvailableBatchDownloadForScope($dossierScope),
        );
    }

    public function testMarkAllForScopeAsOutdated(): void
    {
        $targetDossier = WooDecisionFactory::createOne();
        $otherDossier = WooDecisionFactory::createOne();
        $scope = BatchDownloadScope::forWooDecision($targetDossier);

        $targetBatch = new BatchDownload($scope, new DateTimeImmutable('+1 month'));
        $targetBatch->complete('target.zip', 1, 1);
        $this->repository->save($targetBatch);

        $outdatedBatch = new BatchDownload($scope, new DateTimeImmutable('+1 month'));
        $outdatedBatch->markAsOutdated();
        $unchangedExpiration = $outdatedBatch->getExpiration();
        $this->repository->save($outdatedBatch);

        $otherBatch = new BatchDownload(
            BatchDownloadScope::forWooDecision($otherDossier),
            new DateTimeImmutable('+1 month'),
        );
        $otherBatch->complete('other.zip', 1, 1);
        $this->repository->save($otherBatch);

        $now = new DateTimeImmutable();
        $changed = $this->repository->markAllForScopeAsOutdated($scope);

        // clear local UnitOfWork state for BatchDownload so subsequent finds read fresh DB state
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        Assert::isInstanceOf($entityManager, EntityManager::class);
        /** @var EntityManager $entityManager */
        $entityManager->clear();

        self::assertSame(1, $changed);

        $foundTarget = $this->repository->find($targetBatch->getId());
        Assert::isInstanceOf($foundTarget, BatchDownload::class);
        self::assertSame(BatchDownloadStatus::OUTDATED, $foundTarget->getStatus());

        // compare expiration with second precision to be robust against DB timestamp precision
        $foundOutdated = $this->repository->find($outdatedBatch->getId());
        Assert::isInstanceOf($foundOutdated, BatchDownload::class);
        self::assertEquals(
            $unchangedExpiration->format('Y-m-d H:i:s'),
            $foundOutdated->getExpiration()->format('Y-m-d H:i:s'),
        );

        $foundOther = $this->repository->find($otherBatch->getId());
        Assert::isInstanceOf($foundOther, BatchDownload::class);
        self::assertSame(BatchDownloadStatus::COMPLETED, $foundOther->getStatus());

        $expected = $now->modify('+15 minutes');
        self::assertLessThanOrEqual(
            60,
            abs($foundTarget->getExpiration()->getTimestamp() - $expected->getTimestamp()),
        );
    }

    public function testExists(): void
    {
        $batchDownload = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision(WooDecisionFactory::createOne()),
        ]);

        $this->assertTrue($this->repository->exists($batchDownload->getId()));
    }

    public function testExistsReturnsFalse(): void
    {
        $this->assertFalse($this->repository->exists(Uuid::v6()));
    }
}
