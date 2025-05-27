<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Upload\WooDecision\DocumentUploadVoter;
use App\Domain\Uploader\UploadRequest;
use App\Domain\Uploader\UploadService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DocumentUploadVoterTest extends MockeryTestCase
{
    private DocumentUploadVoter $voter;

    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private Security&MockInterface $security;

    public function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->security = \Mockery::mock(Security::class);

        $this->voter = new DocumentUploadVoter(
            $this->wooDecisionRepository,
            $this->security,
        );

        parent::setUp();
    }

    public function testAbstainForUnknownAttribute(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = \Mockery::mock(UploadRequest::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $request, ['foo']),
        );
    }

    public function testAbstainForUnknownSubject(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $subject = new \stdClass();

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $subject, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testAbstainForWrongUploadGroup(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::MAIN_DOCUMENTS,
            new InputBag(),
        );

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testAbstainForMissingDossierId(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(),
        );

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testAccessGranted(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag([
                'dossierId' => $dossierId = 'woo-123',
            ]),
        );

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::CONCEPT);
        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($wooDecision);

        $this->security->expects('isGranted')->with('AuthMatrix.dossier.create', $wooDecision)->andReturnTrue();

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testAccessDeniedWhenDossierIsNotFound(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag([
                'dossierId' => $dossierId = 'woo-123',
            ]),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturnNull();

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testAccessDeniedWhenUserHasNoDossierAccess(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag([
                'dossierId' => $dossierId = 'woo-123',
            ]),
        );

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($wooDecision);

        $this->security->expects('isGranted')->with('AuthMatrix.dossier.update', $wooDecision)->andReturnFalse();

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }
}
