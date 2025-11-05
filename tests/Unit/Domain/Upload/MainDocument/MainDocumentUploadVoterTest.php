<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\MainDocument;

use App\Domain\Upload\Attachment\AttachmentUploadVoter;
use App\Domain\Upload\Dossier\DossierUploadRequestValidator;
use App\Domain\Upload\UploadRequest;
use App\Domain\Upload\UploadService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MainDocumentUploadVoterTest extends MockeryTestCase
{
    private AttachmentUploadVoter $voter;
    private DossierUploadRequestValidator&MockInterface $validator;

    protected function setUp(): void
    {
        $this->validator = \Mockery::mock(DossierUploadRequestValidator::class);

        $this->voter = new AttachmentUploadVoter(
            $this->validator,
        );

        parent::setUp();
    }

    public function testReturnsAbstainForUnsupportedInput(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::ATTACHMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::ATTACHMENTS)
            ->andReturnFalse();

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testReturnsDeniedForInvalidRequest(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::ATTACHMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::ATTACHMENTS)
            ->andReturnTrue();

        $this->validator->expects('isValidUploadRequest')->with($request)->andReturnFalse();

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }

    public function testReturnsGrantedForValidInput(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::ATTACHMENTS,
            new InputBag(),
        );

        $this->validator->expects('supports')
            ->with(UploadService::SECURITY_ATTRIBUTE, $request, UploadGroupId::ATTACHMENTS)
            ->andReturnTrue();

        $this->validator->expects('isValidUploadRequest')->with($request)->andReturnTrue();

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $request, [UploadService::SECURITY_ATTRIBUTE]),
        );
    }
}
