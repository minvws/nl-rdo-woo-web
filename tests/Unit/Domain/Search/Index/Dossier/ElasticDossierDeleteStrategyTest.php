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

    protected function setUp(): void
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

    public function testDeleteWithOverride(): void
    {
        /** @var ElasticDossierDeleteStrategy&MockInterface $strategy */
        $strategy = \Mockery::mock(ElasticDossierDeleteStrategy::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $dossier = \Mockery::mock(Covenant::class);

        $strategy->expects('delete')->with($dossier);

        $strategy->deleteWithOverride($dossier);
    }
}
