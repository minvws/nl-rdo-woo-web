<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\DossierWizard;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Shared\Service\DossierWizard\StepStatus;
use Shared\Tests\Unit\UnitTestCase;

class DossierWizardStatusTest extends UnitTestCase
{
    private WooDecision&MockInterface $dossier;
    private DossierWizardStatus $dossierWizardStatus;
    private StepStatus&MockInterface $detailsStep;
    private StepStatus&MockInterface $contentStep;
    private StepStatus&MockInterface $publicationStep;

    protected function setUp(): void
    {
        $this->dossier = Mockery::mock(WooDecision::class);
        $this->detailsStep = Mockery::mock(StepStatus::class);
        $this->contentStep = Mockery::mock(StepStatus::class);
        $this->publicationStep = Mockery::mock(StepStatus::class);

        $this->dossierWizardStatus = new DossierWizardStatus(
            $this->dossier,
            StepName::CONTENT,
            StepName::DECISION,
            [
                StepName::DETAILS->value => $this->detailsStep,
                StepName::CONTENT->value => $this->contentStep,
                StepName::PUBLICATION->value => $this->publicationStep,
            ]
        );
    }

    public function testIsCurrentStepAccessibleInConceptModeForConcept(): void
    {
        $this->dossier->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);
        $this->contentStep->expects('isAccessible')
            ->andReturn(true);

        self::assertTrue($this->dossierWizardStatus->isCurrentStepAccessibleInConceptMode());
    }

    public function testIsCurrentStepAccessibleInConceptModeForPublished(): void
    {
        $this->dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        self::assertFalse($this->dossierWizardStatus->isCurrentStepAccessibleInConceptMode());
    }

    public function testIsCurrentStepAccessibleInEditModeForConcept(): void
    {
        $this->dossier->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);
        $this->contentStep->expects('isAccessible')
            ->andReturn(true);

        self::assertTrue($this->dossierWizardStatus->isCurrentStepAccessibleInConceptMode());
    }

    public function testIsCurrentStepAccessibleInEditModeForPublished(): void
    {
        $this->dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        self::assertFalse($this->dossierWizardStatus->isCurrentStepAccessibleInConceptMode());
    }

    public function testGetDossier(): void
    {
        self::assertEquals($this->dossierWizardStatus->getDossier(), $this->dossier);
    }

    public function testGetContentStep(): void
    {
        self::assertEquals($this->contentStep, $this->dossierWizardStatus->getContentStep());
    }

    public function testGetPublicationStep(): void
    {
        self::assertEquals($this->publicationStep, $this->dossierWizardStatus->getPublicationStep());
    }

    public function testGetContentPath(): void
    {
        $this->contentStep->expects('getRouteName')
            ->andReturn('content-path');

        self::assertEquals('content-path', $this->dossierWizardStatus->getContentPath());
    }
}
