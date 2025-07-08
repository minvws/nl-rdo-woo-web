<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Dossier;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\Dossier\DossierUploadRequestValidator;
use App\Domain\Upload\UploadRequest;
use App\Domain\Upload\UploadService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

class DossierUploadRequestValidatorTest extends MockeryTestCase
{
    private DossierUploadRequestValidator $requestValidator;

    private DossierRepository&MockInterface $dossierRepository;
    private Security&MockInterface $security;

    public function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->security = \Mockery::mock(Security::class);

        $this->requestValidator = new DossierUploadRequestValidator(
            $this->dossierRepository,
            $this->security,
        );

        parent::setUp();
    }

    public function testSupportsReturnsFalseForUnknownAttribute(): void
    {
        self::assertFalse(
            $this->requestValidator->supports(
                'foo',
                'bar',
                UploadGroupId::WOO_DECISION_DOCUMENTS,
            ),
        );
    }

    public function testSupportsReturnsFalseForUnsupportedSubject(): void
    {
        self::assertFalse(
            $this->requestValidator->supports(
                UploadService::SECURITY_ATTRIBUTE,
                'bar',
                UploadGroupId::WOO_DECISION_DOCUMENTS,
            ),
        );
    }

    public function testSupportsReturnsFalseForUnsupportedGroup(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::MAIN_DOCUMENTS,
            new InputBag(),
        );

        self::assertFalse(
            $this->requestValidator->supports(
                UploadService::SECURITY_ATTRIBUTE,
                $request,
                UploadGroupId::WOO_DECISION_DOCUMENTS,
            ),
        );
    }

    public function testSupportsReturnsFalseForMissingDossierId(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(),
        );

        self::assertFalse(
            $this->requestValidator->supports(
                UploadService::SECURITY_ATTRIBUTE,
                $request,
                UploadGroupId::WOO_DECISION_DOCUMENTS,
            ),
        );
    }

    public function testSupportsReturnsTrueForValidRequest(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(['dossierId' => 123]),
        );

        self::assertTrue(
            $this->requestValidator->supports(
                UploadService::SECURITY_ATTRIBUTE,
                $request,
                UploadGroupId::WOO_DECISION_DOCUMENTS,
            ),
        );
    }

    public function testIsValidUploadRequestReturnsFalseForUnknownDossier(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(['dossierId' => 123]),
        );

        $this->dossierRepository->expects('find')->with(123)->andReturnNull();

        self::assertFalse(
            $this->requestValidator->isValidUploadRequest($request),
        );
    }

    public function testIsValidUploadRequestReturnsFalseDossierAccessNotGranted(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(['dossierId' => 123]),
        );

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $this->dossierRepository->expects('find')->with(123)->andReturn($dossier);

        $this->security->expects('isGranted')->with('AuthMatrix.dossier.update', $dossier)->andReturnFalse();

        self::assertFalse(
            $this->requestValidator->isValidUploadRequest($request),
        );
    }

    public function testIsValidUploadRequestReturnsTrueForValidRequest(): void
    {
        $request = new UploadRequest(
            1,
            1,
            'foo-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(['dossierId' => 123]),
        );

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $this->dossierRepository->expects('find')->with(123)->andReturn($dossier);

        $this->security->expects('isGranted')->with('AuthMatrix.dossier.update', $dossier)->andReturnTrue();

        self::assertTrue(
            $this->requestValidator->isValidUploadRequest($request),
        );
    }
}
