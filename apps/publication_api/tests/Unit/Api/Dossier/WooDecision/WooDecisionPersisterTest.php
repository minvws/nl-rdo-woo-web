<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentSynchronizer;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\WooDecisionInquirySynchronizer;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionPersister;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class WooDecisionPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $documentInquiryNumbers = new ArrayCollection();

        $wooDecision = new WooDecision();
        $wooDecision->setMainDocument(
            new WooDecisionMainDocument($wooDecision, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $wooDecisionInquirySynchronizer = Mockery::mock(WooDecisionInquirySynchronizer::class);
        $wooDecisionInquirySynchronizer->expects('snapshot')->with($wooDecision)->andReturn($documentInquiryNumbers);

        $wooDecisionPersister = new WooDecisionPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
            Mockery::mock(WooDecisionRepository::class),
            Mockery::mock(WooDecisionDocumentSynchronizer::class),
            $wooDecisionInquirySynchronizer,
        );

        $wooDecisionSnapshot = $wooDecisionPersister->snapshot($wooDecision);

        self::assertInstanceOf(MetadataSnapshot::class, $wooDecisionSnapshot->mainDocument);
        self::assertSame($documentInquiryNumbers, $wooDecisionSnapshot->documentInquiryNumbers);
    }

    public function testPersistDispatchesDossierCreatedEvent(): void
    {
        $wooDecisionRequestDto = new WooDecisionRequestDto(
            Uuid::v6(),
            new WooDecisionMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
            ),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            DecisionType::PUBLIC,
            PublicationReason::WOO_REQUEST,
            $this->getFaker()->plainDate(),
        );

        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($wooDecision);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('save')->with($wooDecision, true);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($wooDecision);
        $dossierSupportService->expects('synchronizeArtifacts')->with($wooDecision);

        $wooDecisionInquirySynchronizer = Mockery::mock(WooDecisionInquirySynchronizer::class);
        $wooDecisionInquirySynchronizer->expects('apply')->with($wooDecision, $wooDecisionRequestDto->documents);

        $wooDecisionDocumentSynchronizer = Mockery::mock(WooDecisionDocumentSynchronizer::class);
        $wooDecisionDocumentSynchronizer->expects('synchronizeRefersTo')->with($wooDecisionRequestDto->documents);

        $wooDecisionPersister = new WooDecisionPersister(
            $dossierSupportService,
            $dossierValidator,
            $wooDecisionRepository,
            $wooDecisionDocumentSynchronizer,
            $wooDecisionInquirySynchronizer,
        );

        $wooDecisionPersister->persist($wooDecision, $wooDecisionRequestDto, null, []);
    }

    public function testPersistDispatchesThePublicationEvents(): void
    {
        $wooDecisionRequestDto = new WooDecisionRequestDto(
            Uuid::v6(),
            new WooDecisionMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
            ),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            DecisionType::PUBLIC,
            PublicationReason::WOO_REQUEST,
            $this->getFaker()->plainDate(),
        );

        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::PUBLISHED);
        $wooDecision->setMainDocument(
            new WooDecisionMainDocument($wooDecision, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $documentInquiryNumbers = new ArrayCollection();
        $wooDecisionSnapshot = WooDecisionSnapshot::of($wooDecision, $documentInquiryNumbers);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($wooDecision);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('save')->with($wooDecision, true);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($wooDecision, $wooDecisionSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($wooDecision);

        $wooDecisionInquirySynchronizer = Mockery::mock(WooDecisionInquirySynchronizer::class);
        $wooDecisionInquirySynchronizer->expects('apply')->with($wooDecision, $wooDecisionRequestDto->documents, $documentInquiryNumbers);

        $wooDecisionDocumentSynchronizer = Mockery::mock(WooDecisionDocumentSynchronizer::class);
        $wooDecisionDocumentSynchronizer->expects('synchronizeRefersTo')->with($wooDecisionRequestDto->documents);

        $wooDecisionPersister = new WooDecisionPersister(
            $dossierSupportService,
            $dossierValidator,
            $wooDecisionRepository,
            $wooDecisionDocumentSynchronizer,
            $wooDecisionInquirySynchronizer,
        );

        $wooDecisionPersister->persist($wooDecision, $wooDecisionRequestDto, $wooDecisionSnapshot, $attachmentEvents);
    }
}
