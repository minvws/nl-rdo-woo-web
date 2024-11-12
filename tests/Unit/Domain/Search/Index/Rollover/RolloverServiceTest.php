<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\Domain\Search\Index\Rollover\DocumentCounts;
use App\Domain\Search\Index\Rollover\MappingService;
use App\Domain\Search\Index\Rollover\RolloverParameters;
use App\Domain\Search\Index\Rollover\RolloverService;
use App\ElasticConfig;
use App\Message\InitiateElasticRolloverMessage;
use App\Message\SetElasticAliasMessage;
use App\Repository\DocumentRepository;
use App\Repository\WooDecisionRepository;
use App\Service\Search\Object\ObjectHandler;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class RolloverServiceTest extends UnitTestCase
{
    private RolloverService $rolloverService;
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private ObjectHandler&MockInterface $objectHandler;
    private MessageBusInterface&MockInterface $messageBus;
    private MappingService&MockInterface $mappingService;

    public function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->objectHandler = \Mockery::mock(ObjectHandler::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->mappingService = \Mockery::mock(MappingService::class);

        $this->rolloverService = new RolloverService(
            $this->wooDecisionRepository,
            $this->documentRepository,
            $this->objectHandler,
            $this->messageBus,
            $this->mappingService,
        );

        parent::setUp();
    }

    public function testGetDetailsFromIndicesReturnsNullWhenNoWriteIndexIsFound(): void
    {
        $indexA = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            ['woopie-read'],
        );

        $indexB = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            [],
        );

        self::assertNull(
            $this->rolloverService->getDetailsFromIndices([$indexA, $indexB])
        );
    }

    public function testGetDetailsFromIndicesReturnsWriteIndex(): void
    {
        $indexA = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            ['woopie-read'],
        );

        $indexB = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            ['woopie-write'],
        );

        $this->wooDecisionRepository->expects('count')->andReturn(123);
        $this->documentRepository->expects('getCountAndPageSum')->andReturn(new DocumentCounts(77, 88));
        $this->objectHandler->expects('getObjectCount')->with($indexB->name, 'dossier')->andReturn(222);
        $this->objectHandler->expects('getObjectCount')->with($indexB->name, 'document')->andReturn(333);
        $this->objectHandler->expects('getTotalPageCount')->with($indexB->name)->andReturn(444);

        $this->assertMatchesObjectSnapshot(
            $this->rolloverService->getDetailsFromIndices([$indexA, $indexB])
        );
    }

    public function testGetDetails(): void
    {
        $index = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            ['woopie-read', 'woopie-write'],
        );

        $this->wooDecisionRepository->expects('count')->andReturn(123);
        $this->documentRepository->expects('getCountAndPageSum')->andReturn(new DocumentCounts(77, 88));
        $this->objectHandler->expects('getObjectCount')->with($index->name, 'dossier')->andReturn(222);
        $this->objectHandler->expects('getObjectCount')->with($index->name, 'document')->andReturn(333);
        $this->objectHandler->expects('getTotalPageCount')->with($index->name)->andReturn(444);

        $this->assertMatchesObjectSnapshot($this->rolloverService->getDetails($index));
    }

    public function testMakeLive(): void
    {
        $name = 'new-index';

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (SetElasticAliasMessage $message) use ($name) {
                self::assertEquals($name, $message->indexName);
                self::assertEquals(ElasticConfig::READ_INDEX, $message->aliasName);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (SetElasticAliasMessage $message) use ($name) {
                self::assertEquals($name, $message->indexName);
                self::assertEquals(ElasticConfig::WRITE_INDEX, $message->aliasName);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->rolloverService->makeLive($name);
    }

    public function testInitiateRollover(): void
    {
        $params = new RolloverParameters(13);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (InitiateElasticRolloverMessage $message) use ($params) {
                self::assertEquals($params->getMappingVersion(), $message->mappingVersion);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->rolloverService->initiateRollover($params);
    }

    public function testGetDefaultRolloverParameters(): void
    {
        $this->mappingService->expects('getLatestMappingVersion')->andReturn(12);

        $params = $this->rolloverService->getDefaultRolloverParameters();

        self::assertEquals(12, $params->getMappingVersion());
    }
}
