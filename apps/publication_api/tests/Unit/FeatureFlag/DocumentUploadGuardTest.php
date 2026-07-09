<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\FeatureFlag\DocumentUploadGuard;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Unit\UnitTestCase;

class DocumentUploadGuardTest extends UnitTestCase
{
    public function testAssertDocumentUploadIsAllowedPassesWhenDossierIsNewOrConcept(): void
    {
        $guard = new DocumentUploadGuard();
        $dossier = $this->createDossier(DossierStatus::CONCEPT);

        $guard->assertDocumentUploadIsAllowed($dossier);

        $this->addToAssertionCount(1);
    }

    public function testAssertDocumentUploadIsAllowedThrowsWhenFlagIsDisabledAndDossierIsPublished(): void
    {
        $guard = new DocumentUploadGuard();
        $dossier = $this->createDossier(DossierStatus::PUBLISHED);

        $this->expectException(ValidationException::class);

        $guard->assertDocumentUploadIsAllowed($dossier);
    }

    public function testAssertDocumentUploadIsAllowedPassesWhenFlagIsEnabled(): void
    {
        $guard = new DocumentUploadGuard(true);
        $dossier = $this->createDossier(DossierStatus::PUBLISHED);

        $guard->assertDocumentUploadIsAllowed($dossier);

        $this->addToAssertionCount(1);
    }

    private function createDossier(DossierStatus $status): AbstractDossier&MockInterface
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->allows('getStatus')->andReturn($status);

        return $dossier;
    }
}
