<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CovenantMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $covenant = new Covenant();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(CovenantMainDocument::class));

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $covenantMainDocumentSynchronizer = new CovenantMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $covenantMainDocumentSynchronizer->create($covenant, $covenantMainDocumentRequestDto);

        self::assertInstanceOf(CovenantMainDocument::class, $covenant->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $covenant = new Covenant();
        $existingMainDocument = new CovenantMainDocument(
            $covenant,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenant->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $covenantMainDocumentSynchronizer = new CovenantMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $covenantMainDocumentSynchronizer->update($covenant, $covenantMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $covenant->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $covenant = new Covenant();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(CovenantMainDocument::class));

        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $covenantMainDocumentSynchronizer = new CovenantMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $covenantMainDocumentSynchronizer->update($covenant, $covenantMainDocumentRequestDto);

        self::assertInstanceOf(CovenantMainDocument::class, $covenant->getMainDocument());
    }

    public function testDelete(): void
    {
        $covenant = new Covenant();
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($covenant): bool {
                return $deleteMainDocumentCommand->dossierId->equals($covenant->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($covenant->getId())));

        $covenantMainDocumentSynchronizer = new CovenantMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $covenantMainDocumentSynchronizer->delete($covenant);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $covenant = new Covenant();

        $covenantMainDocumentSynchronizer = new CovenantMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $covenantMainDocumentSynchronizer->delete($covenant);

        self::assertNull($covenant->getMainDocument());
    }
}
