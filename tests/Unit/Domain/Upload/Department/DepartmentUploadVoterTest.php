<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Department;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Department\DepartmentService;
use App\Domain\Upload\Department\DepartmentUploadVoter;
use App\Domain\Upload\UploadRequest;
use App\Domain\Upload\UploadService;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class DepartmentUploadVoterTest extends UnitTestCase
{
    private DepartmentRepository&MockInterface $repository;
    private DepartmentService&MockInterface $departmentService;
    private TokenInterface&MockInterface $token;
    private UploadedFile&MockInterface $uploadedFile;

    private DepartmentUploadVoter $voter;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = \Mockery::mock(DepartmentRepository::class);
        $this->departmentService = \Mockery::mock(DepartmentService::class);
        $this->token = \Mockery::mock(TokenInterface::class);
        $this->uploadedFile = \Mockery::mock(UploadedFile::class);

        $this->voter = new DepartmentUploadVoter(
            $this->repository,
            $this->departmentService,
        );
    }

    public function testVotorWithUnknownAttribute(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => '123']),
        );

        $voterResult = $this->voter->vote($this->token, $subject, ['unknown_attribute']);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voterResult);
    }

    public function testVotorWithUnsupportedSubject(): void
    {
        $voterResult = $this->voter->vote($this->token, 'invalid subject', [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voterResult);
    }

    public function testVotorWithUnsupportedGroupId(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::ATTACHMENTS,
            new InputBag(['departmentId' => '123']),
        );

        $voterResult = $this->voter->vote($this->token, $subject, [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voterResult);
    }

    public function testVotorWithMissingDepartmentId(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::DEPARTMENT,
            new InputBag(),
        );

        $voterResult = $this->voter->vote($this->token, $subject, [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voterResult);
    }

    public function testVotorWithNonExistingDepartment(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => $departmentId = '123']),
        );

        $this->repository->shouldReceive('find')->with($departmentId)->once()->andReturnNull();

        $voterResult = $this->voter->vote($this->token, $subject, [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voterResult);
    }

    public function testVotorWithDepartmentServiceUserCanEditLandingPageReturningFalse(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => $departmentId = '123']),
        );

        $this->repository
            ->shouldReceive('find')
            ->with($departmentId)
            ->once()
            ->andReturn($department = \Mockery::mock(Department::class));

        $this->departmentService
            ->shouldReceive('userCanEditLandingpage')
            ->with($department)
            ->once()
            ->andReturnFalse();

        $voterResult = $this->voter->vote($this->token, $subject, [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voterResult);
    }

    public function testVotorWithDepartmentServiceUserCanEditLandingPageReturningTrue(): void
    {
        $subject = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            $this->uploadedFile,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => $departmentId = '123']),
        );

        $this->repository
            ->shouldReceive('find')
            ->with($departmentId)
            ->once()
            ->andReturn($department = \Mockery::mock(Department::class));

        $this->departmentService
            ->shouldReceive('userCanEditLandingpage')
            ->with($department)
            ->once()
            ->andReturnTrue();

        $voterResult = $this->voter->vote($this->token, $subject, [UploadService::SECURITY_ATTRIBUTE]);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voterResult);
    }
}
