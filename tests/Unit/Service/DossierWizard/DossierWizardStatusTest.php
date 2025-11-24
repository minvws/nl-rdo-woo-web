<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\DossierWizard;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Shared\Service\DossierWizard\StepStatus;
use Shared\Tests\Unit\UnitTestCase;

class DossierWizardStatusTest extends UnitTestCase
{
    private WooDecision&MockInterface $dossier;
    private DossierWizardStatus $status;
    private StepStatus&MockInterface $detailsStep;
    private StepStatus&MockInterface $contentStep;
    private StepStatus&MockInterface $publicationStep;

    protected function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->detailsStep = \Mockery::mock(StepStatus::class);
        $this->contentStep = \Mockery::mock(StepStatus::class);
        $this->publicationStep = \Mockery::mock(StepStatus::class);

        $this->status = new DossierWizardStatus(
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

    public function testGetContentStep(): void
    {
        self::assertEquals($this->contentStep, $this->status->getContentStep());
    }

    public function testGetPublicationStep(): void
    {
        self::assertEquals($this->publicationStep, $this->status->getPublicationStep());
    }

    public function testGetContentPath(): void
    {
        $this->contentStep->shouldReceive('getRouteName')->once()->andReturn('content-path');

        self::assertEquals('content-path', $this->status->getContentPath());
    }
}
