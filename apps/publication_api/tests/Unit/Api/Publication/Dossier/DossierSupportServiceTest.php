<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\Dossier\DossierSupportService;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class DossierSupportServiceTest extends UnitTestCase
{
    private AttachmentService&MockInterface $attachmentService;
    private DossierService&MockInterface $dossierService;
    private MainDocumentService&MockInterface $mainDocumentService;
    private OrganisationRepository&MockInterface $organisationRepository;
    private SubjectRepository&MockInterface $subjectRepository;
    private Security&MockInterface $security;
    private DossierSupportService $dossierSupportService;

    protected function setUp(): void
    {
        $this->attachmentService = Mockery::mock(AttachmentService::class);
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->mainDocumentService = Mockery::mock(MainDocumentService::class);
        $this->organisationRepository = Mockery::mock(OrganisationRepository::class);
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->security = Mockery::mock(Security::class);

        $this->dossierSupportService = new DossierSupportService(
            $this->attachmentService,
            Mockery::mock(DepartmentRepository::class),
            Mockery::mock(DossierDispatcher::class),
            $this->dossierService,
            $this->mainDocumentService,
            $this->organisationRepository,
            $this->subjectRepository,
            $this->security,
        );
    }

    public function testGetOrganisationReturnsOrganisation(): void
    {
        $organisationId = Uuid::v6();
        $organisation = Mockery::mock(Organisation::class);

        $this->organisationRepository->expects('find')
            ->with($organisationId)
            ->andReturn($organisation);

        $result = $this->dossierSupportService->getOrganisation(['organisationId' => $organisationId]);

        self::assertSame($organisation, $result);
    }

    public function testGetOrganisationThrowsWhenKeyMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->dossierSupportService->getOrganisation([]);
    }

    public function testGetOrganisationThrowsWhenOrganisationIdIsNotUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->dossierSupportService->getOrganisation(['organisationId' => 'not-a-uuid-object']);
    }

    public function testGetOrganisationThrowsWhenNotFound(): void
    {
        $organisationId = Uuid::v6();

        $this->organisationRepository->expects('find')
            ->with($organisationId)
            ->andReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->dossierSupportService->getOrganisation(['organisationId' => $organisationId]);
    }

    public function testGetSubjectReturnsNullWhenSubjectIdIsNull(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $data = $this->createDossierRequestDto(subjectId: null);

        $this->subjectRepository->expects('findByOrganisationAndId')->never();

        $result = $this->dossierSupportService->getSubject($data, $organisation);

        self::assertNull($result);
    }

    public function testGetSubjectReturnsSubject(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $subjectId = Uuid::v6();
        $subject = Mockery::mock(Subject::class);
        $data = $this->createDossierRequestDto(subjectId: $subjectId);

        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, $subjectId)
            ->andReturn($subject);

        $result = $this->dossierSupportService->getSubject($data, $organisation);

        self::assertSame($subject, $result);
    }

    public function testGetSubjectThrowsWhenNotFound(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $subjectId = Uuid::v6();
        $data = $this->createDossierRequestDto(subjectId: $subjectId);

        $this->subjectRepository->expects('findByOrganisationAndId')
            ->with($organisation, $subjectId)
            ->andReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->dossierSupportService->getSubject($data, $organisation);
    }

    public function testValidateDossierThrowsWhenSecurityDenied(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);

        $this->security->expects('isGranted')
            ->with('AuthMatrix.dossier.update', $dossier)
            ->andReturn(false);

        $this->expectException(ValidationException::class);

        $this->dossierSupportService->validateDossier($dossier);
    }

    public function testValidateDossierSucceeds(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);

        $this->security->expects('isGranted')
            ->with('AuthMatrix.dossier.update', $dossier)
            ->andReturn(true);

        $this->dossierService->expects('validate')
            ->with($dossier, [
                DossierValidationGroup::DETAILS,
                DossierValidationGroup::DECISION,
                DossierValidationGroup::DOCUMENTS,
                DossierValidationGroup::PUBLICATION,
                DossierValidationGroup::CONTENT,
            ]);
        $this->dossierService->expects('refreshDossier')->never();

        $this->dossierSupportService->validateDossier($dossier);
    }

    public function testValidateDossierRefreshesAndRethrowsOnValidationFailure(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $violations = new ConstraintViolationList();
        $validationFailedException = new ValidationFailedException($dossier, $violations);

        $this->security->expects('isGranted')
            ->with('AuthMatrix.dossier.update', $dossier)
            ->andReturn(true);

        $this->dossierService->expects('validate')
            ->andThrow($validationFailedException);

        $this->dossierService->expects('refreshDossier')->with($dossier);

        $this->expectException(ValidationException::class);

        $this->dossierSupportService->validateDossier($dossier);
    }

    public function testValidateAttachmentsSucceeds(): void
    {
        $attachment1 = $this->createAttachmentWithExternalId('ext-1');
        $attachment2 = $this->createAttachmentWithExternalId('ext-2');

        $this->attachmentService->expects('validate')->with([$attachment1, $attachment2]);
        $this->attachmentService->expects('refreshAttachments')->never();

        $this->dossierSupportService->validateAttachments([$attachment1, $attachment2]);
    }

    public function testValidateAttachmentsThrowsOnDuplicateExternalIds(): void
    {
        $attachment1 = $this->createAttachmentWithExternalId('same-id');
        $attachment2 = $this->createAttachmentWithExternalId('same-id');

        $this->attachmentService->expects('validate')->never();

        $this->expectException(ValidationException::class);

        $this->dossierSupportService->validateAttachments([$attachment1, $attachment2]);
    }

    public function testValidateAttachmentsRefreshesAndRethrowsOnValidationFailure(): void
    {
        $attachment = $this->createAttachmentWithExternalId('ext-1');
        $violations = new ConstraintViolationList([
            $this->createViolation('fileName', 'must not be blank'),
        ]);
        $validationFailedException = new ValidationFailedException([$attachment], $violations);

        $this->attachmentService->expects('validate')
            ->andThrow($validationFailedException);

        $this->attachmentService->expects('refreshAttachments')->with([$attachment]);

        self::expectException(ValidationException::class);
        self::expectExceptionMessage('attachments.fileName: must not be blank');

        $this->dossierSupportService->validateAttachments([$attachment]);
    }

    public function testValidateMainDocumentSucceeds(): void
    {
        $mainDocument = Mockery::mock(AbstractMainDocument::class);

        $this->mainDocumentService->expects('validate')->with($mainDocument);
        $this->mainDocumentService->expects('refreshMainDocument')->never();

        $this->dossierSupportService->validateMainDocument($mainDocument);
    }

    public function testValidateMainDocumentRefreshesAndRethrowsOnValidationFailure(): void
    {
        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $violations = new ConstraintViolationList([
            $this->createViolation('fileName', 'must not be blank'),
        ]);
        $validationFailedException = new ValidationFailedException($mainDocument, $violations);

        $this->mainDocumentService->expects('validate')
            ->andThrow($validationFailedException);

        $this->mainDocumentService->expects('refreshMainDocument')->with($mainDocument);

        self::expectException(ValidationException::class);
        self::expectExceptionMessage('mainDocument.fileName: must not be blank');

        $this->dossierSupportService->validateMainDocument($mainDocument);
    }

    public function testPrefixViolationsPropertyPathPrefixesCorrectly(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('fileName', 'must not be blank'),
            $this->createViolation('formalDate', 'invalid date'),
        ]);

        $result = $this->dossierSupportService->prefixViolationsPropertyPath($violations, 'attachments.');

        self::assertCount(2, $result);
        self::assertSame('attachments.fileName', $result->get(0)->getPropertyPath());
        self::assertSame('attachments.formalDate', $result->get(1)->getPropertyPath());
    }

    public function testPrefixViolationsPropertyPathPreservesViolationFields(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('title', 'too short', 'abc', 'length_error'),
        ]);

        $result = $this->dossierSupportService->prefixViolationsPropertyPath($violations, 'doc.');

        self::assertCount(1, $result);

        $violation = $result->get(0);
        self::assertSame('doc.title', $violation->getPropertyPath());
        self::assertSame('too short', $violation->getMessage());
        self::assertSame('abc', $violation->getInvalidValue());
        self::assertSame('length_error', $violation->getCode());
    }

    public function testPrefixViolationsPropertyPathWithEmptyList(): void
    {
        $result = $this->dossierSupportService->prefixViolationsPropertyPath(new ConstraintViolationList(), 'prefix.');

        self::assertCount(0, $result);
    }

    private function createDossierRequestDto(?Uuid $subjectId): AbstractDossierRequestDto
    {
        return new class($subjectId) extends AbstractDossierRequestDto {
            public function __construct(?Uuid $subjectId)
            {
                parent::__construct(
                    departmentId: Uuid::v6(),
                    dossierNumber: 'DOS-001',
                    subjectId: $subjectId,
                    summary: 'Summary',
                    title: 'Title',
                );
            }
        };
    }

    private function createAttachmentWithExternalId(string $externalId): AbstractAttachment&MockInterface
    {
        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->allows('getExternalId')->andReturn(ExternalId::create($externalId));

        return $attachment;
    }

    private function createViolation(
        string $propertyPath,
        string $message,
        mixed $invalidValue = null,
        ?string $code = null,
    ): ConstraintViolation {
        return new ConstraintViolation(
            $message,
            null,
            [],
            null,
            $propertyPath,
            $invalidValue,
            null,
            $code,
        );
    }
}
