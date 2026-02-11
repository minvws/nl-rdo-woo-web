<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use Shared\Domain\Search\Index\Rollover\InitiateElasticRolloverCommand;
use Shared\Domain\Search\Index\Rollover\MainTypeCount;
use Shared\Domain\Search\Index\Rollover\MappingService;
use Shared\Domain\Search\Index\Rollover\RolloverCounter;
use Shared\Domain\Search\Index\Rollover\RolloverParameters;
use Shared\Domain\Search\Index\Rollover\RolloverService;
use Shared\Domain\Search\Index\Rollover\SetElasticAliasCommand;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class RolloverServiceTest extends UnitTestCase
{
    private RolloverService $rolloverService;
    private MessageBusInterface&MockInterface $messageBus;
    private RolloverCounter&MockInterface $counter;
    private MappingService&MockInterface $mappingService;

    protected function setUp(): void
    {
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->mappingService = Mockery::mock(MappingService::class);
        $this->counter = Mockery::mock(RolloverCounter::class);

        $this->rolloverService = new RolloverService(
            $this->messageBus,
            $this->mappingService,
            $this->counter,
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

        $this->counter->expects('getEntityCounts')->with($indexB)->andReturn([
            new MainTypeCount(ElasticDocumentType::WOO_DECISION, 5, 0),
        ]);

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

        $this->counter->expects('getEntityCounts')->andReturn([
            new MainTypeCount(ElasticDocumentType::WOO_DECISION, 10, 20),
        ]);

        $this->assertMatchesObjectSnapshot($this->rolloverService->getDetails($index));
    }

    public function testMakeLive(): void
    {
        $name = 'new-index';

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (SetElasticAliasCommand $message) use ($name) {
                self::assertEquals($name, $message->indexName);
                self::assertEquals(ElasticConfig::READ_INDEX, $message->aliasName);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (SetElasticAliasCommand $message) use ($name) {
                self::assertEquals($name, $message->indexName);
                self::assertEquals(ElasticConfig::WRITE_INDEX, $message->aliasName);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->rolloverService->makeLive($name);
    }

    public function testInitiateRollover(): void
    {
        $params = new RolloverParameters(13);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (InitiateElasticRolloverCommand $message) use ($params) {
                self::assertEquals($params->getMappingVersion(), $message->mappingVersion);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->rolloverService->initiateRollover($params);
    }

    public function testGetDefaultRolloverParameters(): void
    {
        $this->mappingService->expects('getLatestMappingVersion')->andReturn(12);

        $params = $this->rolloverService->getDefaultRolloverParameters();

        self::assertEquals(12, $params->getMappingVersion());
    }
}
