<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\ElasticConfig;
use App\Message\InitiateElasticRolloverMessage;
use App\MessageHandler\InitializeElasticRolloverHandler;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class InitializeElasticRolloverHandlerTest extends UnitTestCase
{
    private ElasticIndexManager&MockInterface $elasticIndexManager;
    private LoggerInterface&MockInterface $logger;
    private ingestDispatcher&MockInterface $ingestDispatcher;
    private InitializeElasticRolloverHandler $handler;

    public function setUp(): void
    {
        $this->elasticIndexManager = \Mockery::mock(ElasticIndexManager::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);

        $this->handler = new InitializeElasticRolloverHandler(
            $this->elasticIndexManager,
            $this->logger,
            $this->ingestDispatcher,
        );
    }

    public function testInvokeLogsExceptionAsError(): void
    {
        $message = new InitiateElasticRolloverMessage(
            20,
            'foo',
        );

        $this->elasticIndexManager->expects('create')->andThrow(new \RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new InitiateElasticRolloverMessage(
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
