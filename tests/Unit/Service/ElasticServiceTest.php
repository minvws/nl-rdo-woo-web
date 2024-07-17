<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\ElasticConfig;
use App\Entity\Department;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Elastic\ElasticService;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

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

    public function testUpdatePage(): void
    {
        $id = 'foo-123';
        $pageNr = 12;
        $content = 'foo bar';

        $this->logger->shouldReceive('debug');

        $this->elasticClient->expects('update')->with(\Mockery::on(
            static function (array $input) use ($id, $pageNr, $content) {
                return $input['id'] === $id
                    && $input['body']['script']['params']['page']['page_nr'] === $pageNr
                    && $input['body']['script']['params']['page']['content'] === $content;
            }
        ));

        $this->elasticService->updatePage($id, $pageNr, $content);
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

    public function testUpdateDepartment(): void
    {
        $departmentId = Uuid::v6();
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getId')->andReturn($departmentId);
        $department->shouldReceive('getName')->andReturn('Foo Bar');

        $this->elasticClient->expects('updateByQuery')->with(\Mockery::on(
            static function (array $input) use ($departmentId) {
                return $input['body']['query']['bool']['should'][0]['match']['departments.id'] === $departmentId
                    && $input['body']['script']['params']['department']['name'] === 'Foo Bar';
            }
        ));

        $this->elasticService->updateDepartment($department);
    }

    public function testUpdateAllDocumentsForDossier(): void
    {
        $dossierNr = 'foo-bar-123';
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);

        $dossierDoc = ['foo' => 'bar'];

        $this->logger->shouldReceive('debug');

        $this->elasticClient->expects('updateByQuery')->with(\Mockery::on(
            static function (array $input) use ($dossierNr, $dossierDoc) {
                return $input['body']['query']['bool']['must'][1]['match']['dossier_nr'] === $dossierNr
                    && $input['body']['script']['params']['dossier'] === $dossierDoc;
            }
        ));

        $this->elasticService->updateAllDocumentsForDossier($dossier, $dossierDoc);
    }
}
