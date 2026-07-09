<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Unit\UnitTestCase;

class DossierUpdateGuardTest extends UnitTestCase
{
    public function testAssertDossierIsEditablePassesWhenDossierIsNewOrConcept(): void
    {
        $service = new DossierUpdateGuard();
        $dossier = $this->createDossier(DossierStatus::CONCEPT);

        $service->assertDossierIsEditable($dossier);

        $this->addToAssertionCount(1);
    }

    public function testAssertDossierIsEditableThrowsWhenFlagIsDisabledAndDossierIsNotEditable(): void
    {
        $service = new DossierUpdateGuard();
        $dossier = $this->createDossier(DossierStatus::PUBLISHED);

        $this->expectException(ValidationException::class);

        $service->assertDossierIsEditable($dossier);
    }

    public function testAssertDossierIsEditablePassesWhenFlagIsEnabled(): void
    {
        $service = new DossierUpdateGuard(true);
        $dossier = $this->createDossier(DossierStatus::PUBLISHED);

        $service->assertDossierIsEditable($dossier);

        $this->addToAssertionCount(1);
    }

    private function createDossier(DossierStatus $status): AbstractDossier&MockInterface
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->allows('getStatus')->andReturn($status);

        return $dossier;
    }
}
