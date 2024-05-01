<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DossierWizard;

use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DossierWizard\DossierWizardStatus;
use App\Service\DossierWizard\StepStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierWizardStatusTest extends MockeryTestCase
{
    private WooDecision&MockInterface $dossier;
    private DossierWizardStatus $status;
    private StepStatus&MockInterface $detailsStep;
    private StepStatus&MockInterface $contentStep;
    private StepStatus&MockInterface $publicationStep;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->detailsStep = \Mockery::mock(StepStatus::class);
        $this->contentStep = \Mockery::mock(StepStatus::class);
        $this->publicationStep = \Mockery::mock(StepStatus::class);

        $this->status = new DossierWizardStatus(
            $this->dossier,
            StepName::CONTENT,
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
}
