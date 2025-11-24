<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\BatchDownload;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\Publication\BatchDownload\BatchDownloadFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Uid\Uuid;

class BatchDownloadRepositoryTest extends SharedWebTestCase
{
    private BatchDownloadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(BatchDownloadRepository::class);
    }

    public function testSaveAndRemove(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $download = new BatchDownload(
            scope: BatchDownloadScope::forWooDecision($wooDecision->_real()),
            expiration: new \DateTimeImmutable('+1 day'),
        );

        $id = $download->getId();

        self::assertNull(
            $this->repository->find($id)
        );

        $this->repository->save($download);
        $result = $this->repository->find($id);
        self::assertEquals($download, $result);

        $this->repository->remove($download);
        self::assertNull(
            $this->repository->find($id)
        );
    }

    public function testFindExpiredArchives(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $downloadA = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision($wooDecision->_real()),
            'expiration' => new \DateTimeImmutable('+1 day'),
        ]);
        $downloadB = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision($wooDecision->_real()),
            'expiration' => new \DateTimeImmutable('-1 day'),
        ]);

        $result = $this->repository->findExpiredBatchDownloads();

        self::assertCount(1, $result);
        self::assertEquals($downloadB->_real(), $result[0]);
    }

    public function testGetBestAvailableBatchDownloadForScopeWithWooDecision(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();

        $dossierScope = BatchDownloadScope::forWooDecision($dossierA->_real());
        $pendingDownload = BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);

        $failedDownload = BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);
        $failedDownload->markAsFailed();
        $failedDownload->_save();

        $olderDownload = BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);
        $olderDownload->complete('123.zip', 456, 789);
        $olderDownload->_save();

        $expectedDownload = BatchDownloadFactory::createOne([
            'expiration' => new \DateTimeImmutable('+2 month'),
            'scope' => $dossierScope,
        ]);
        $expectedDownload->complete('123.zip', 456, 789);
        $expectedDownload->_save();

        $otherDossierDownload = BatchDownloadFactory::createOne([
            'expiration' => new \DateTimeImmutable('+3 month'),
            'scope' => BatchDownloadScope::forWooDecision($dossierB->_real()),
        ]);
        $otherDossierDownload->complete('123.zip', 456, 789);
        $otherDossierDownload->_save();

        $inquiry = InquiryFactory::createOne();
        BatchDownloadScope::forInquiryAndWooDecision($inquiry->_real(), $dossierA->_real());
        BatchDownloadFactory::createOne([
            'scope' => $dossierScope,
        ]);

        self::assertEquals(
            $expectedDownload->_real(),
            $this->repository->getBestAvailableBatchDownloadForScope($dossierScope),
        );
    }

    public function testExists(): void
    {
        $batchDownload = BatchDownloadFactory::createOne([
            'scope' => BatchDownloadScope::forWooDecision(WooDecisionFactory::createOne()->_real()),
        ]);

        $this->assertTrue($this->repository->exists($batchDownload->getId()));
    }

    public function testExistsReturnsFalse(): void
    {
        $this->assertFalse($this->repository->exists(Uuid::v6()));
    }
}
