<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentSynchronizer;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;

final class OtherPublicationMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $otherPublication = new OtherPublication();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(OtherPublicationMainDocument::class));

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $otherPublicationMainDocumentSynchronizer->create($otherPublication, $otherPublicationMainDocumentRequestDto);

        self::assertInstanceOf(OtherPublicationMainDocument::class, $otherPublication->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $otherPublication = new OtherPublication();
        $existingMainDocument = new OtherPublicationMainDocument(
            $otherPublication,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $otherPublication->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $otherPublicationMainDocumentSynchronizer->update($otherPublication, $otherPublicationMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $otherPublication->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $otherPublication = new OtherPublication();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(OtherPublicationMainDocument::class));

        $otherPublicationMainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $otherPublicationMainDocumentSynchronizer->update($otherPublication, $otherPublicationMainDocumentRequestDto);

        self::assertInstanceOf(OtherPublicationMainDocument::class, $otherPublication->getMainDocument());
    }

    public function testDelete(): void
    {
        $otherPublication = new OtherPublication();
        $otherPublication->setMainDocument(
            new OtherPublicationMainDocument(
                $otherPublication,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')->with($otherPublication);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($otherPublication): bool {
                return $deleteMainDocumentCommand->dossierId->equals($otherPublication->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($otherPublication->getId())));

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        $otherPublicationMainDocumentSynchronizer->delete($otherPublication);
    }

    public function testDeleteWhenTransitionIsNotAllowed(): void
    {
        $otherPublication = new OtherPublication();
        $otherPublication->setMainDocument(
            new OtherPublicationMainDocument(
                $otherPublication,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')
            ->with($otherPublication)
            ->andThrow(new ValidationException(ConstraintViolationList::createFromMessage('Deletion is not allowed')));

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        self::expectException(ValidationException::class);
        $otherPublicationMainDocumentSynchronizer->delete($otherPublication);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $otherPublication = new OtherPublication();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $otherPublicationMainDocumentSynchronizer = new OtherPublicationMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        $otherPublicationMainDocumentSynchronizer->delete($otherPublication);

        self::assertNull($otherPublication->getMainDocument());
    }
}
