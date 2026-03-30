<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
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
    private DocumentVoter $voter;

    private InquirySessionService&MockInterface $inquirySessionService;

    protected function setUp(): void
    {
        $this->inquirySessionService = Mockery::mock(InquirySessionService::class);

        $this->voter = new DocumentVoter(
            $this->inquirySessionService,
        );

        parent::setUp();
    }

    public function testAbstainForUnknownAttribute(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $document = Mockery::mock(Document::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $document, ['foo']),
        );
    }

    public function testAbstainWhenDocumentHasMultipleDossiers(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $document = Mockery::mock(Document::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $document, ['foo']),
        );
    }

    public function testAbstainForUnknownSubject(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new stdClass(), [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedForPublishedDossier(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([
            $dossier,
        ]));

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsNotPublishedAndNotPreviewAndDocumentNotInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection());

        $this->inquirySessionService->expects('getInquiries')->andReturn(['foo', 'bar']);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsPreviewAndNotInSessionAndDocumentNotInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getId')->andReturn(Uuid::v6());

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getId')->andReturn(Uuid::v6());

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $this->inquirySessionService->expects('getInquiries')->andReturn(['foo', 'bar']);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDossierIsPreviewAndInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getId')->andReturn(Uuid::v6());

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getId')->andReturn($inquiryBId = Uuid::v6());

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->times(2)->andReturn(DossierStatus::PREVIEW);
        $dossier->expects('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([
            $dossier,
        ]));

        $this->inquirySessionService->expects('getInquiries')->andReturn(['foo', 'bar', $inquiryBId]);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDocumentInSession(): void
    {
        $token = Mockery::mock(TokenInterface::class);

        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getId')->andReturn(Uuid::v6());

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getId')->andReturn($inquiryBId = Uuid::v6());

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->times(2)->andReturn(DossierStatus::PREVIEW);
        $dossier->expects('getInquiries')->andReturn(new ArrayCollection());

        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->expects('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $this->inquirySessionService->expects('getInquiries')->times(2)->andReturn(['foo', 'bar', $inquiryBId]);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }
}
