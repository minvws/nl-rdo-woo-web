<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\Dossier\ElasticDossierDeleteStrategy;
use App\Service\Elastic\ElasticService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class ElasticDossierDeleteStrategyTest extends MockeryTestCase
{
    private ElasticService&MockInterface $elasticService;
    private ElasticDossierDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->strategy = new ElasticDossierDeleteStrategy($this->elasticService);

        parent::setUp();
    }

    public function testDelete(): void
    {
        $dossier = \Mockery::mock(Covenant::class);

        $this->elasticService->expects('removeDossier')->with($dossier);

        $this->strategy->delete($dossier);
    }
}
