<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentSynchronizer;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class WooDecisionMainDocumentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with(Mockery::type(WooDecisionMainDocument::class));

        $wooDecision = new WooDecision();
        $wooDecisionMainDocumentRequestDto = new WooDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $wooDecisionMainDocumentSynchronizer = new WooDecisionMainDocumentSynchronizer($dossierMainDocumentValidator);
        $wooDecisionMainDocumentSynchronizer->create($wooDecision, $wooDecisionMainDocumentRequestDto);

        self::assertInstanceOf(WooDecisionMainDocument::class, $wooDecision->getMainDocument());
    }

    public function testUpdate(): void
    {
        $wooDecision = new WooDecision();
        $existingMainDocument = new WooDecisionMainDocument(
            $wooDecision,
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $wooDecision->setMainDocument($existingMainDocument);

        $dossierMainDocumentValidator = Mockery::mock(DossierMainDocumentValidator::class);
        $dossierMainDocumentValidator->expects('validate')->with($existingMainDocument);

        $wooDecisionMainDocumentRequestDto = new WooDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );

        $wooDecisionMainDocumentSynchronizer = new WooDecisionMainDocumentSynchronizer($dossierMainDocumentValidator);
        $wooDecisionMainDocumentSynchronizer->update($wooDecision, $wooDecisionMainDocumentRequestDto);

        self::assertSame($existingMainDocument, $wooDecision->getMainDocument());
    }
}
