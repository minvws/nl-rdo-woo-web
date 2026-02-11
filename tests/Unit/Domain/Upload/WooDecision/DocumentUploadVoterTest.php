<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Upload\Dossier\DossierUploadRequestValidator;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Domain\Upload\WooDecision\DocumentUploadVoter;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DocumentUploadVoterTest extends UnitTestCase
{
    private DocumentUploadVoter $voter;
    private DossierUploadRequestValidator&MockInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Mockery::mock(DossierUploadRequestValidator::class);

        $this->voter = new DocumentUploadVoter(
            $this->validator,
        );

        parent::setUp();
    }

    public function testReturnsAbstainForUnsupportedInput(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            UploadGroupId::MAIN_DOCUMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnFalse();

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testReturnsDeniedForInvalidRequest(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->validator->expects('isValidUploadRequest')->with($request)->andReturnFalse();

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testReturnsGrantedForValidInput(): void
    {
        $token = Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->validator->expects('isValidUploadRequest')->with($request)->andReturnTrue();

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }
}
