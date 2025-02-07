<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\ElasticConfig;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Elastic\ElasticService;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ElasticServiceTest extends MockeryTestCase
{
    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private ElasticService $elasticService;

    public function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->elasticService = new ElasticService(
            $this->elasticClient,
            $this->logger,
        );

        parent::setUp();
    }

    public function testUpdateDocument(): void
    {
        $id = 'foo-123';
        $docValues = ['foo' => 123];
        $document = \Mockery::mock(ElasticDocument::class);
        $document->shouldReceive('getId')->andReturn($id);
        $document->shouldReceive('getDocumentValues')->andReturn($docValues);

        $this->elasticClient->expects('update')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
            'body' => [
                'doc' => $docValues,
                'doc_as_upsert' => true,
            ],
        ]);

        $this->elasticService->updateDocument($document);
    }

    public function testGetDocument(): void
    {
        $id = 'foo-123';
        $documentData = ['foo' => 'bar'];

        $result = \Mockery::mock(Elasticsearch::class);
        $result->shouldReceive('asArray')->andReturn($documentData);

        $this->elasticClient->expects('get')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ])->andReturn($result);

        self::assertEquals(
            new TypeArray($documentData),
            $this->elasticService->getDocument($id),
        );
    }

    public function testGetAndSetLogger(): void
    {
        self::assertSame(
            $this->logger,
            $this->elasticService->getLogger(),
        );

        $newLogger = \Mockery::mock(LoggerInterface::class);
        $this->elasticService->setLogger($newLogger);

        self::assertSame(
            $newLogger,
            $this->elasticService->getLogger(),
        );
    }

    public function testRemoveDocumentSkipsWhenDocumentDoesntExist(): void
    {
        $id = 'foo-123';

        $result = \Mockery::mock(Elasticsearch::class);
        $result->shouldReceive('asBool')->andReturnFalse();

        $this->elasticClient->expects('exists')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ])->andReturn($result);

        $this->elasticService->removeDocument($id);
    }

    public function testRemoveDocument(): void
    {
        $id = 'foo-123';

        $result = \Mockery::mock(Elasticsearch::class);
        $result->shouldReceive('asBool')->andReturnTrue();

        $this->elasticClient->expects('exists')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ])->andReturn($result);

        $this->elasticClient->expects('delete')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ]);

        $this->elasticService->removeDocument($id);
    }

    public function testRemoveDossierSuccessful(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($id = 'foo-123');

        $this->elasticClient->expects('delete')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ]);

        $this->elasticService->removeDossier($dossier);
    }

    public function testRemoveDossierNotFoundIsSilentlyIgnored(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($id = 'foo-123');

        $this->elasticClient->expects('delete')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ])->andThrows(new ClientResponseException('', 404));

        $this->elasticService->removeDossier($dossier);
    }

    public function testRemoveDossierExceptionIsThrown(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($id = 'foo-123');

        $this->elasticClient->expects('delete')->with([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ])->andThrows(new ClientResponseException('', 500));

        $this->expectException(ClientResponseException::class);

        $this->elasticService->removeDossier($dossier);
    }
}
