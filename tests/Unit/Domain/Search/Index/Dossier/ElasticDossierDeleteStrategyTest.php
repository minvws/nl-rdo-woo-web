<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Dossier;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Search\Index\Dossier\ElasticDossierDeleteStrategy;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;

final class ElasticDossierDeleteStrategyTest extends UnitTestCase
{
    private ElasticService&MockInterface $elasticService;
    private ElasticDossierDeleteStrategy $strategy;

    protected function setUp(): void
    {
        $this->elasticService = Mockery::mock(ElasticService::class);
        $this->strategy = new ElasticDossierDeleteStrategy($this->elasticService);

        parent::setUp();
    }

    public function testDelete(): void
    {
        $dossier = Mockery::mock(Covenant::class);

        $this->elasticService->expects('removeDossier')->with($dossier);

        $this->strategy->delete($dossier);
    }

    public function testDeleteWithOverride(): void
    {
        /** @var ElasticDossierDeleteStrategy&MockInterface $strategy */
        $strategy = Mockery::mock(ElasticDossierDeleteStrategy::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $dossier = Mockery::mock(Covenant::class);

        $strategy->expects('delete')->with($dossier);

        $strategy->deleteWithOverride($dossier);
    }
}
