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

        $this->indexUpdater->update($dossier, $dossierDoc);
    }
}
