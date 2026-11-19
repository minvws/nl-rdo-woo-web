<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DossierMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;

final class DossierMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $covenant = new Covenant();
        $covenantMainDocument = new CovenantMainDocument(
            $covenant,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $mainDocumentRequestMapper = Mockery::mock(MainDocumentRequestMapperInterface::class);
        $mainDocumentRequestMapper->expects('create')
            ->with($covenant, $covenantMainDocumentRequestDto)
            ->andReturn($covenantMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($covenantMainDocument);

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dossierMainDocumentSynchronizer->create($covenant, $covenantMainDocumentRequestDto, $mainDocumentRequestMapper);

        self::assertSame($covenantMainDocument, $covenant->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $covenant = new Covenant();
        $covenantMainDocument = new CovenantMainDocument(
            $covenant,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenant->setMainDocument($covenantMainDocument);
        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $mainDocumentRequestMapper = Mockery::mock(MainDocumentRequestMapperInterface::class);
        $mainDocumentRequestMapper->expects('update')
            ->with($covenant, $covenantMainDocumentRequestDto)
            ->andReturn($covenantMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($covenantMainDocument);

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dossierMainDocumentSynchronizer->update($covenant, $covenantMainDocumentRequestDto, $mainDocumentRequestMapper);

        self::assertSame($covenantMainDocument, $covenant->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $covenant = new Covenant();
        $covenantMainDocument = new CovenantMainDocument(
            $covenant,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $mainDocumentRequestMapper = Mockery::mock(MainDocumentRequestMapperInterface::class);
        $mainDocumentRequestMapper->expects('create')
            ->with($covenant, $covenantMainDocumentRequestDto)
            ->andReturn($covenantMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($covenantMainDocument);

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dossierMainDocumentSynchronizer->update($covenant, $covenantMainDocumentRequestDto, $mainDocumentRequestMapper);

        self::assertSame($covenantMainDocument, $covenant->getMainDocument());
    }

    public function testDelete(): void
    {
        $covenant = new Covenant();
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')->with($covenant);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($covenant): bool {
                return $deleteMainDocumentCommand->dossierId->equals($covenant->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($covenant->getId())));

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        $dossierMainDocumentSynchronizer->delete($covenant);
    }

    public function testDeleteWhenTransitionIsNotAllowed(): void
    {
        $covenant = new Covenant();
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')
            ->with($covenant)
            ->andThrow(new ValidationException(ConstraintViolationList::createFromMessage('Deletion is not allowed')));

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );

        self::expectException(ValidationException::class);
        $dossierMainDocumentSynchronizer->delete($covenant);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $covenant = new Covenant();

        $dossierMainDocumentSynchronizer = new DossierMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $dossierMainDocumentSynchronizer->delete($covenant);

        self::assertNull($covenant->getMainDocument());
    }
}
