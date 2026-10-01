<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inquiry\InquirySessionService;
use Shared\Service\Security\DocumentVoter;
use Shared\Service\Security\DossierVoter;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

class DocumentVoterTest extends UnitTestCase
{
    public function testAbstainForUnknownAttribute(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $document = Mockery::mock(Document::class);

        $documentVoter = new DocumentVoter(Mockery::mock(InquirySessionService::class));

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $documentVoter->vote($token, $document, [$this->getFaker()->word()]),
        );
    }

    public function testAbstainForUnknownSubject(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $documentVoter = new DocumentVoter(Mockery::mock(InquirySessionService::class));

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $documentVoter->vote($token, new stdClass(), [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedForSinglePublishedWooDecision(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$wooDecision]));

        $documentVoter = new DocumentVoter(Mockery::mock(InquirySessionService::class));

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenOneOfMultipleWooDecisionsIsPublished(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $conceptWooDecision = Mockery::mock(WooDecision::class);
        $conceptWooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $publishedWooDecision = Mockery::mock(WooDecision::class);
        $publishedWooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            $conceptWooDecision,
            $publishedWooDecision,
        ]));

        $documentVoter = new DocumentVoter(Mockery::mock(InquirySessionService::class));

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenAllWooDecisionsAreConceptAndDocumentNotInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $wooDecisionA = Mockery::mock(WooDecision::class);
        $wooDecisionA->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $wooDecisionB = Mockery::mock(WooDecision::class);
        $wooDecisionB->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$wooDecisionA, $wooDecisionB]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection());

        $inquirySessionService = Mockery::mock(InquirySessionService::class);
        $inquirySessionService->expects('getInquiries')->andReturn([Uuid::v6()->toRfc4122()]);

        $documentVoter = new DocumentVoter($inquirySessionService);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenOneOfMultipleWooDecisionsIsPreviewAndItsInquiryIsInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getId')->andReturn($inquiryId = Uuid::v6());

        $conceptWooDecision = Mockery::mock(WooDecision::class);
        $conceptWooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $previewWooDecision = Mockery::mock(WooDecision::class);
        $previewWooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::PREVIEW);
        $previewWooDecision->expects('getInquiries')->andReturn(new ArrayCollection([$inquiry]));

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            $conceptWooDecision,
            $previewWooDecision,
        ]));

        $inquirySessionService = Mockery::mock(InquirySessionService::class);
        $inquirySessionService->expects('getInquiries')->andReturn([$inquiryId]);

        $documentVoter = new DocumentVoter($inquirySessionService);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenPreviewWooDecisionInquiryIsNotInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getId')->andReturn(Uuid::v6());

        $conceptWooDecision = Mockery::mock(WooDecision::class);
        $conceptWooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $previewWooDecision = Mockery::mock(WooDecision::class);
        $previewWooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::PREVIEW);
        $previewWooDecision->expects('getInquiries')->andReturn(new ArrayCollection([$inquiry]));

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([
            $conceptWooDecision,
            $previewWooDecision,
        ]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection());

        $inquirySessionService = Mockery::mock(InquirySessionService::class);
        $inquirySessionService->expects('getInquiries')->times(2)->andReturn([Uuid::v6()->toRfc4122()]);

        $documentVoter = new DocumentVoter($inquirySessionService);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDocumentHasNoWooDecisionsEvenWithItsInquiryInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection());

        $documentVoter = new DocumentVoter(Mockery::mock(InquirySessionService::class));

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDocumentInquiryIsInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getId')->andReturn($inquiryId = Uuid::v6());

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection([$wooDecision]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection([$inquiry]));

        $inquirySessionService = Mockery::mock(InquirySessionService::class);
        $inquirySessionService->expects('getInquiries')->andReturn([$inquiryId]);

        $documentVoter = new DocumentVoter($inquirySessionService);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $documentVoter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }
}
