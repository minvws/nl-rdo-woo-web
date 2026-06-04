<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Uploads\MainDocument;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\DossierLookup;
use PublicationApi\Api\Publication\OrganisationLookup;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentHandler;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;

class UploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToHandler(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $content = Mockery::mock(StreamInterface::class);

        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);
        $request->expects('getContent')->andReturn($content);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(Covenant::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $dossier->expects('getMainDocument')->andReturn($mainDocument);

        $uploadMainDocumentHandler = Mockery::mock(UploadMainDocumentHandler::class);
        $uploadMainDocumentHandler->expects('handle')
            ->with($dossier, $mainDocument, $content);

        $processor = new UploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
        );

        $processor->process($request, $dossierRepository, AbstractMainDocument::class);
    }

    public function testProcessThrowsWhenDossierHasNoMainDocument(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $uploadMainDocumentHandler = Mockery::mock(UploadMainDocumentHandler::class);

        $processor = new UploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
        );

        $this->expectException(ValidationException::class);

        $processor->process($request, $dossierRepository, AbstractMainDocument::class);
    }

    public function testProcessThrowsWhenMainDocumentNotFound(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(Covenant::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $dossier->expects('getMainDocument')->andReturnNull();

        $uploadMainDocumentHandler = Mockery::mock(UploadMainDocumentHandler::class);

        $processor = new UploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
        );

        $this->expectException(ValidationException::class);

        $processor->process($request, $dossierRepository, AbstractMainDocument::class);
    }

    public function testProcessThrowsWhenMainDocumentIsWrongType(): void
    {
        $organisationId = $this->getFaker()->uuid();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $request->expects('getOrganisationId')->andReturn($organisationId);
        $request->expects('getDossierExternalId')->andReturn($dossierExternalId);

        $organisation = Mockery::mock(Organisation::class);
        $organisationLookup = Mockery::mock(OrganisationLookup::class);
        $organisationLookup
            ->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $dossierRepository = Mockery::mock(DossierRepositoryWithExternalId::class);
        $dossier = Mockery::mock(Covenant::class);
        $dossierLookup = Mockery::mock(DossierLookup::class);
        $dossierLookup
            ->expects('find')
            ->with($dossierRepository, $organisation, $dossierExternalId)
            ->andReturn($dossier);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $dossier->expects('getMainDocument')->andReturn($mainDocument);

        $uploadMainDocumentHandler = Mockery::mock(UploadMainDocumentHandler::class);

        $processor = new UploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
        );

        $this->expectException(ValidationException::class);

        $processor->process($request, $dossierRepository, CovenantMainDocument::class);
    }
}
