<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\Domain\Search\Index\Rollover\InitiateElasticRolloverCommand;
use App\Domain\Search\Index\Rollover\InitiateElasticRolloverHandler;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class InitiateElasticRolloverHandlerTest extends UnitTestCase
{
    private ElasticIndexManager&MockInterface $elasticIndexManager;
    private LoggerInterface&MockInterface $logger;
    private ingestDispatcher&MockInterface $ingestDispatcher;
    private InitiateElasticRolloverHandler $handler;

    public function setUp(): void
    {
        $this->elasticIndexManager = \Mockery::mock(ElasticIndexManager::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);

        $this->handler = new InitiateElasticRolloverHandler(
            $this->elasticIndexManager,
            $this->logger,
            $this->ingestDispatcher,
        );
    }

    public function testInvokeLogsExceptionAsError(): void
    {
        $message = new InitiateElasticRolloverCommand(
            20,
            'foo',
        );

        $this->elasticIndexManager->expects('create')->andThrow(new \RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new InitiateElasticRolloverCommand(
            $version = 20,
            $name = 'foo',
        );

        $this->elasticIndexManager->expects('create')->with($name, $version);
        $this->elasticIndexManager->expects('switch')->with(
            ElasticConfig::WRITE_INDEX,
            '*',
            $name,
        );

        $this->ingestDispatcher->expects('dispatchIngestAllDossiersCommand');

        $this->handler->__invoke($message);
    }
}
