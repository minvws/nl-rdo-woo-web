<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Publication\Dossier\ViewModel\DossierNotificationsFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Spatie\Snapshots\MatchesSnapshots;

class DossierNotificationsFactoryTest extends UnitTestCase
{
    use MatchesSnapshots;

    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DossierNotificationsFactory $factory;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->factory = new DossierNotificationsFactory(
            $this->wooDecisionRepository,
        );
    }

    public function testMakeForWooDecision(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('isCompleted')->andReturnFalse();

        $this->wooDecisionRepository
            ->expects('getNotificationCounts')
            ->with($wooDecision)
            ->andReturn([
                'missing_uploads' => 2,
                'suspended' => 3,
                'withdrawn' => 4,
            ]);

        $result = $this->factory->make($wooDecision);

        $this->assertMatchesSnapshot($result);
    }

    public function testMakeForIncompleteCovenant(): void
    {
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('isCompleted')->andReturnFalse();

        $result = $this->factory->make($covenant);

        $this->assertMatchesSnapshot($result);
    }

    public function testMakeForCompletedCovenant(): void
    {
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('isCompleted')->andReturnTrue();

        $result = $this->factory->make($covenant);

        $this->assertMatchesSnapshot($result);
    }
}
