<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentSynchronizer;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RequestForAdviceMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $requestForAdvice = new RequestForAdvice();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(RequestForAdviceMainDocument::class));

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );

        $requestForAdviceMainDocumentSynchronizer = new RequestForAdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $requestForAdviceMainDocumentSynchronizer->create($requestForAdvice, $requestForAdviceMainDocumentRequestDto);

        self::assertInstanceOf(RequestForAdviceMainDocument::class, $requestForAdvice->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $requestForAdvice = new RequestForAdvice();
        $existingMainDocument = new RequestForAdviceMainDocument(
            $requestForAdvice,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $requestForAdvice->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );

        $requestForAdviceMainDocumentSynchronizer = new RequestForAdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $requestForAdviceMainDocumentSynchronizer->update($requestForAdvice, $requestForAdviceMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $requestForAdvice->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $requestForAdvice = new RequestForAdvice();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(RequestForAdviceMainDocument::class));

        $requestForAdviceMainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );

        $requestForAdviceMainDocumentSynchronizer = new RequestForAdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $requestForAdviceMainDocumentSynchronizer->update($requestForAdvice, $requestForAdviceMainDocumentRequestDto);

        self::assertInstanceOf(RequestForAdviceMainDocument::class, $requestForAdvice->getMainDocument());
    }

    public function testDelete(): void
    {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setMainDocument(
            new RequestForAdviceMainDocument(
                $requestForAdvice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($requestForAdvice): bool {
                return $deleteMainDocumentCommand->dossierId->equals($requestForAdvice->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($requestForAdvice->getId())));

        $requestForAdviceMainDocumentSynchronizer = new RequestForAdviceMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $requestForAdviceMainDocumentSynchronizer->delete($requestForAdvice);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $requestForAdvice = new RequestForAdvice();

        $requestForAdviceMainDocumentSynchronizer = new RequestForAdviceMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $requestForAdviceMainDocumentSynchronizer->delete($requestForAdvice);

        self::assertNull($requestForAdvice->getMainDocument());
    }
}
