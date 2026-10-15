<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;

final class DispositionMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $disposition = new Disposition();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(DispositionMainDocument::class));

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dispositionMainDocumentSynchronizer->create($disposition, $dispositionMainDocumentRequestDto);

        self::assertInstanceOf(DispositionMainDocument::class, $disposition->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $disposition = new Disposition();
        $existingMainDocument = new DispositionMainDocument(
            $disposition,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $disposition->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dispositionMainDocumentSynchronizer->update($disposition, $dispositionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $disposition->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $disposition = new Disposition();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(DispositionMainDocument::class));

        $dispositionMainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $dispositionMainDocumentSynchronizer->update($disposition, $dispositionMainDocumentRequestDto);

        self::assertInstanceOf(DispositionMainDocument::class, $disposition->getMainDocument());
    }

    public function testDelete(): void
    {
        $disposition = new Disposition();
        $disposition->setMainDocument(
            new DispositionMainDocument(
                $disposition,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')->with($disposition);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($disposition): bool {
                return $deleteMainDocumentCommand->dossierId->equals($disposition->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($disposition->getId())));

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        $dispositionMainDocumentSynchronizer->delete($disposition);
    }

    public function testDeleteWhenTransitionIsNotAllowed(): void
    {
        $disposition = new Disposition();
        $disposition->setMainDocument(
            new DispositionMainDocument(
                $disposition,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')
            ->with($disposition)
            ->andThrow(new ValidationException(ConstraintViolationList::createFromMessage('Deletion is not allowed')));

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        self::expectException(ValidationException::class);
        $dispositionMainDocumentSynchronizer->delete($disposition);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $disposition = new Disposition();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validateDelete')->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $dispositionMainDocumentSynchronizer = new DispositionMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            $messageBus,
        );

        $dispositionMainDocumentSynchronizer->delete($disposition);

        self::assertNull($disposition->getMainDocument());
    }
}
