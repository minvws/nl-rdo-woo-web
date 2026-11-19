<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Uploads\MainDocument;

use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument\CovenantUploadMainDocumentProcessor;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Api\OrganisationLookup;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentHandler;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use stdClass;
use Symfony\Component\Uid\Uuid;

class UploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToHandler(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);
        $content = Mockery::mock(StreamInterface::class);

        $uploadMainDocumentRequestDto = new UploadMainDocumentRequestDto($content, $organisationId, $dossierExternalId);

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

        $uploadMainDocumentProcessor = new CovenantUploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
            $dossierRepository,
            AbstractMainDocument::class,
        );

        $uploadMainDocumentProcessor->process($uploadMainDocumentRequestDto, new Put());
    }

    public function testProcessThrowsWhenDossierHasNoMainDocument(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $uploadMainDocumentRequestDto = new UploadMainDocumentRequestDto(Mockery::mock(StreamInterface::class), $organisationId, $dossierExternalId);

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

        $uploadMainDocumentProcessor = new CovenantUploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
            $dossierRepository,
            AbstractMainDocument::class,
        );

        $this->expectException(ValidationException::class);

        $uploadMainDocumentProcessor->process($uploadMainDocumentRequestDto, new Put());
    }

    public function testProcessThrowsWhenMainDocumentNotFound(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $uploadMainDocumentRequestDto = new UploadMainDocumentRequestDto(Mockery::mock(StreamInterface::class), $organisationId, $dossierExternalId);

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

        $uploadMainDocumentProcessor = new CovenantUploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
            $dossierRepository,
            AbstractMainDocument::class,
        );

        $this->expectException(ValidationException::class);

        $uploadMainDocumentProcessor->process($uploadMainDocumentRequestDto, new Put());
    }

    public function testProcessThrowsWhenMainDocumentIsWrongType(): void
    {
        $organisationId = Uuid::v6();
        $dossierExternalId = Mockery::mock(ExternalId::class);

        $uploadMainDocumentRequestDto = new UploadMainDocumentRequestDto(Mockery::mock(StreamInterface::class), $organisationId, $dossierExternalId);

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

        $uploadMainDocumentProcessor = new CovenantUploadMainDocumentProcessor(
            $dossierLookup,
            $organisationLookup,
            $uploadMainDocumentHandler,
            $dossierRepository,
            CovenantMainDocument::class,
        );

        $this->expectException(ValidationException::class);

        $uploadMainDocumentProcessor->process($uploadMainDocumentRequestDto, new Put());
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $uploadMainDocumentProcessor = new CovenantUploadMainDocumentProcessor(
            Mockery::mock(DossierLookup::class),
            Mockery::mock(OrganisationLookup::class),
            Mockery::mock(UploadMainDocumentHandler::class),
            Mockery::mock(DossierRepositoryWithExternalId::class),
            AbstractMainDocument::class,
        );

        $this->expectException(ValidationException::class);

        $uploadMainDocumentProcessor->process(new stdClass(), new Put());
    }
}
