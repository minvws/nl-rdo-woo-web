<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Inquiry;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\WooDecisionInquirySynchronizer;
use PublicationApi\Domain\Inquiry\InquiryService;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class WooDecisionInquirySynchronizerTest extends UnitTestCase
{
    public function testGetKeys(): void
    {
        $externalId = $this->getFaker()->externalId();

        $document = new Document();
        $document->setExternalId($externalId);

        $wooDecision = new WooDecision();
        $wooDecision->addDocument($document);

        $wooDecisionInquirySynchronizer = new WooDecisionInquirySynchronizer(Mockery::mock(InquiryService::class));

        $documentInquiryNumbers = $wooDecisionInquirySynchronizer->snapshot($wooDecision);

        self::assertSame([$externalId->toString()], $documentInquiryNumbers->getKeys());
    }

    public function testSnapshotFaislWhenADocumentHasNoExternalId(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->addDocument(new Document());

        $wooDecisionInquirySynchronizer = new WooDecisionInquirySynchronizer(Mockery::mock(InquiryService::class));

        $this->expectException(ValidationException::class);

        $wooDecisionInquirySynchronizer->snapshot($wooDecision);
    }

    public function testApplyAddsTheRequestedInquiryNumbersToTheChangeset(): void
    {
        $externalId = $this->getFaker()->externalId();
        $inquiryNumber = $this->getFaker()->bothify('inq-####');

        $document = new Document();
        $document->setExternalId($externalId);

        $wooDecision = new WooDecision();
        $wooDecision->setOrganisation(new Organisation());
        $wooDecision->addDocument($document);

        $inquiryService = Mockery::mock(InquiryService::class);
        $inquiryService->expects('applyChangesetSync')->with(
            Mockery::on(static function (InquiryChangeset $inquiryChangeset) use ($inquiryNumber, $document): bool {
                return $inquiryChangeset->getChanges() === [
                    $inquiryNumber => [
                        InquiryChangeset::ADD_DOCUMENTS => [$document->getId()],
                        InquiryChangeset::DEL_DOCUMENTS => [],
                        InquiryChangeset::ADD_DOSSIERS => [],
                    ],
                ];
            }),
        );

        $wooDecisionInquirySynchronizer = new WooDecisionInquirySynchronizer($inquiryService);

        $wooDecisionInquirySynchronizer->apply(
            $wooDecision,
            [
                new WooDecisionDocumentRequestDto(
                    [$inquiryNumber],
                    $this->getFaker()->plainDate(),
                    $this->getFaker()->documentId(),
                    $externalId,
                    null,
                    $this->getFaker()->fileName(),
                    array_map(Ground::from(...), $this->getFaker()->grounds()),
                    false,
                    Judgement::PUBLIC,
                    [],
                    [],
                    null,
                    SourceType::PDF,
                    null,
                    $this->getFaker()->publicationContext(),
                ),
            ],
        );
    }
}
