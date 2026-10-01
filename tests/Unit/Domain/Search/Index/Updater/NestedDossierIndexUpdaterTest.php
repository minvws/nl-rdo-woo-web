<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Updater\NestedDossierIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\ElasticConfigFactory;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

class NestedDossierIndexUpdaterTest extends UnitTestCase
{
    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private NestedDossierIndexUpdater $indexUpdater;

    protected function setUp(): void
    {
        $this->elasticClient = Mockery::mock(ElasticClientInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->indexUpdater = new NestedDossierIndexUpdater(
            $this->elasticClient,
            $this->logger,
            ElasticConfigFactory::default(),
        );

        parent::setUp();
    }

    public function testUpdate(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId->toRfc4122')->andReturn($dossierId = 'foo-bar-123');

        $dossierDoc = ['foo' => 'bar'];

        $this->elasticClient->expects('updateByQuery')->with(Mockery::capture($input));

        $this->indexUpdater->update($dossier, $dossierDoc);

        Assert::isArray($input);
        $body = $input['body'];
        Assert::isArray($body);
        $query = $body['query'];
        Assert::isArray($query);
        $script = $body['script'];
        Assert::isArray($script);

        self::assertEquals([
            'must' => [
                ['terms' => ['type' => ElasticDocumentType::getSubTypeValues()]],
                ['nested' => [
                    'path' => 'dossiers',
                    'query' => ['term' => ['dossiers.id' => $dossierId]],
                ]],
            ],
        ], $query['bool']);

        self::assertEquals(['dossier' => $dossierDoc], $script['params']);
    }
}
