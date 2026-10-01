<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use Mockery;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdviceMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $advice = new Advice();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(AdviceMainDocument::class));

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );

        $adviceMainDocumentSynchronizer = new AdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $adviceMainDocumentSynchronizer->create($advice, $adviceMainDocumentRequestDto);

        self::assertInstanceOf(AdviceMainDocument::class, $advice->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $advice = new Advice();
        $existingMainDocument = new AdviceMainDocument(
            $advice,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $advice->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );

        $adviceMainDocumentSynchronizer = new AdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $adviceMainDocumentSynchronizer->update($advice, $adviceMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $advice->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $advice = new Advice();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(AdviceMainDocument::class));

        $adviceMainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );

        $adviceMainDocumentSynchronizer = new AdviceMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $adviceMainDocumentSynchronizer->update($advice, $adviceMainDocumentRequestDto);

        self::assertInstanceOf(AdviceMainDocument::class, $advice->getMainDocument());
    }

    public function testDelete(): void
    {
        $advice = new Advice();
        $advice->setMainDocument(
            new AdviceMainDocument(
                $advice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($advice): bool {
                return $deleteMainDocumentCommand->dossierId->equals($advice->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($advice->getId())));

        $adviceMainDocumentSynchronizer = new AdviceMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $adviceMainDocumentSynchronizer->delete($advice);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $advice = new Advice();

        $adviceMainDocumentSynchronizer = new AdviceMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $adviceMainDocumentSynchronizer->delete($advice);

        self::assertNull($advice->getMainDocument());
    }
}
