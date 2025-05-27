<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Updater;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\Updater\NestedDossierIndexUpdater;
use App\Service\Elastic\ElasticClientInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class NestedDossierIndexUpdaterTest extends MockeryTestCase
{
    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private NestedDossierIndexUpdater $indexUpdater;

    public function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->indexUpdater = new NestedDossierIndexUpdater(
            $this->elasticClient,
            $this->logger,
        );

        parent::setUp();
    }

    public function testUpdate(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($dossierId = 'foo-bar-123');

        $dossierDoc = ['foo' => 'bar'];

        $this->logger->shouldReceive('debug');

        $this->elasticClient->expects('updateByQuery')->with(\Mockery::on(
            static fn (array $input) => $input['body']['query']['bool']['must'][1]['nested']['query']['term']['dossiers.id'] === $dossierId
                && $input['body']['script']['params']['dossier'] === $dossierDoc
        ));

        $this->indexUpdater->update($dossier, $dossierDoc);
    }
}
