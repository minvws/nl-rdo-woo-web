<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Tests\Factory\Publication\BatchDownload\BatchDownloadFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BatchDownloadRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): BatchDownloadRepository
    {
        /** @var BatchDownloadRepository */
        return self::getContainer()->get(BatchDownloadRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testSaveAndRemove(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $download = new BatchDownload(
            scope: BatchDownloadScope::forWooDecision($wooDecision->_real()),
            expiration: new \DateTimeImmutable('+1 day'),
        );

        $id = $download->getId();

        $repository = $this->getRepository();
        self::assertNull(
            $this->getRepository()->find($id)
        );

        $repository->save($download);
        $result = $this->getRepository()->find($id);
        self::assertEquals($download, $result);

        $repository->remove($download);
        self::assertNull(
            $this->getRepository()->find($id)
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

        $repository = $this->getRepository();
        $result = $repository->findExpiredBatchDownloads();

        self::assertCount(1, $result);
        self::assertEquals($downloadB->_real(), $result[0]);
    }

    public function testGetBestAvailableBatchDownloadForEntity(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();

        $scope = BatchDownloadScope::forWooDecision($dossierA->_real());

        $downloadA = BatchDownloadFactory::createOne([
            'scope' => $scope,
        ]);

        $downloadB = BatchDownloadFactory::createOne([
            'scope' => $scope,
        ]);
        $downloadB->markAsFailed();
        $downloadB->_save();

        $downloadC = BatchDownloadFactory::createOne([
            'scope' => $scope,
        ]);
        $downloadC->complete('123.zip', '456', 789);
        $downloadC->_save();

        $downloadD = BatchDownloadFactory::createOne([
            'expiration' => new \DateTimeImmutable('+2 month'),
            'scope' => $scope,
        ]);
        $downloadD->complete('123.zip', '456', 789);
        $downloadD->_save();

        $downloadE = BatchDownloadFactory::createOne([
            'expiration' => new \DateTimeImmutable('+3 month'),
            'scope' => BatchDownloadScope::forWooDecision($dossierB->_real()),
        ]);
        $downloadE->complete('123.zip', '456', 789);
        $downloadE->_save();

        $repository = $this->getRepository();

        self::assertEquals(
            $downloadD->_real(),
            $repository->getBestAvailableBatchDownloadForScope($scope),
        );
    }
}
