<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\SearchDispatcher;
use App\Service\DossierService;
use App\Service\DossierWizard\WizardStatusFactory;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private WizardStatusFactory&MockInterface $statusFactory;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private DossierService $dossierService;

    protected function setUp(): void
    {
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->statusFactory = \Mockery::mock(WizardStatusFactory::class);
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);

        $this->dossierService = new DossierService(
            $this->doctrine,
            $this->statusFactory,
            $this->searchDispatcher
        );

        parent::setUp();
    }

    public function testValidateCompletionForPublishedWooDecision(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->shouldReceive('hasWithdrawnOrSuspendedDocuments')->andReturnTrue();
        $dossier->expects('setCompleted')->with(false);

        $this->doctrine->expects('persist')->with($dossier);
        $this->doctrine->expects('flush');

        $this->statusFactory->expects('getWizardStatus->isCompleted')->andReturnFalse();

        self::assertFalse($this->dossierService->validateCompletion($dossier));
    }
}
