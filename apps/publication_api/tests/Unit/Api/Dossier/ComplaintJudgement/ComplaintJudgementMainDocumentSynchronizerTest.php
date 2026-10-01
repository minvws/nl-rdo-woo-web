<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use Mockery;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ComplaintJudgementMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $complaintJudgement = new ComplaintJudgement();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(ComplaintJudgementMainDocument::class));

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );

        $complaintJudgementMainDocumentSynchronizer = new ComplaintJudgementMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $complaintJudgementMainDocumentSynchronizer->create($complaintJudgement, $complaintJudgementMainDocumentRequestDto);

        self::assertInstanceOf(ComplaintJudgementMainDocument::class, $complaintJudgement->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $complaintJudgement = new ComplaintJudgement();
        $existingMainDocument = new ComplaintJudgementMainDocument(
            $complaintJudgement,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $complaintJudgement->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );

        $complaintJudgementMainDocumentSynchronizer = new ComplaintJudgementMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $complaintJudgementMainDocumentSynchronizer->update($complaintJudgement, $complaintJudgementMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $complaintJudgement->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $complaintJudgement = new ComplaintJudgement();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(ComplaintJudgementMainDocument::class));

        $complaintJudgementMainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );

        $complaintJudgementMainDocumentSynchronizer = new ComplaintJudgementMainDocumentSynchronizer(
            $dossierMainDocumentValidator,
            Mockery::mock(MessageBusInterface::class),
        );
        $complaintJudgementMainDocumentSynchronizer->update($complaintJudgement, $complaintJudgementMainDocumentRequestDto);

        self::assertInstanceOf(ComplaintJudgementMainDocument::class, $complaintJudgement->getMainDocument());
    }

    public function testDelete(): void
    {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setMainDocument(
            new ComplaintJudgementMainDocument(
                $complaintJudgement,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (DeleteMainDocumentCommand $deleteMainDocumentCommand) use ($complaintJudgement): bool {
                return $deleteMainDocumentCommand->dossierId->equals($complaintJudgement->getId());
            }))
            ->andReturn(new Envelope(new DeleteMainDocumentCommand($complaintJudgement->getId())));

        $complaintJudgementMainDocumentSynchronizer = new ComplaintJudgementMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            $messageBus,
        );

        $complaintJudgementMainDocumentSynchronizer->delete($complaintJudgement);
    }

    public function testDeleteWithoutMainDocument(): void
    {
        $complaintJudgement = new ComplaintJudgement();

        $complaintJudgementMainDocumentSynchronizer = new ComplaintJudgementMainDocumentSynchronizer(
            Mockery::mock(DossierMainDocumentValidator::class),
            Mockery::mock(MessageBusInterface::class),
        );

        $complaintJudgementMainDocumentSynchronizer->delete($complaintJudgement);

        self::assertNull($complaintJudgement->getMainDocument());
    }
}
