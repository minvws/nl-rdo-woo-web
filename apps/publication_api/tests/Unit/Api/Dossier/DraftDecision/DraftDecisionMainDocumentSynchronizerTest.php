<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentSynchronizer;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DraftDecisionMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $draftDecision = new DraftDecision();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(DraftDecisionMainDocument::class));

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );

        $draftDecisionMainDocumentSynchronizer = new DraftDecisionMainDocumentSynchronizer($dossierMainDocumentValidator);
        $draftDecisionMainDocumentSynchronizer->create($draftDecision, $draftDecisionMainDocumentRequestDto);

        self::assertInstanceOf(DraftDecisionMainDocument::class, $draftDecision->getMainDocument());
    }

    public function testUpdateWithExistingMainDocument(): void
    {
        $draftDecision = new DraftDecision();
        $existingMainDocument = new DraftDecisionMainDocument(
            $draftDecision,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
            $this->getFaker()->attachmentLanguage(),
        );
        $draftDecision->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );

        $draftDecisionMainDocumentSynchronizer = new DraftDecisionMainDocumentSynchronizer($dossierMainDocumentValidator);
        $draftDecisionMainDocumentSynchronizer->update($draftDecision, $draftDecisionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $draftDecision->getMainDocument());
    }

    public function testUpdateWithNewMainDocument(): void
    {
        $draftDecision = new DraftDecision();

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(DraftDecisionMainDocument::class));

        $draftDecisionMainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );

        $draftDecisionMainDocumentSynchronizer = new DraftDecisionMainDocumentSynchronizer($dossierMainDocumentValidator);
        $draftDecisionMainDocumentSynchronizer->update($draftDecision, $draftDecisionMainDocumentRequestDto);

        self::assertInstanceOf(DraftDecisionMainDocument::class, $draftDecision->getMainDocument());
    }
}
