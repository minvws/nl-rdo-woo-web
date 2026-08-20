<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Dossier\DossierSupportService;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

class DossierSupportServiceTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $subjectRepository;
    private DepartmentRepository&MockInterface $departmentRepository;
    private DossierSupportService $dossierSupportService;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->departmentRepository = Mockery::mock(DepartmentRepository::class);

        $this->dossierSupportService = new DossierSupportService(
            $this->departmentRepository,
            Mockery::mock(DossierDispatcher::class),
            Mockery::mock(DossierService::class),
            $this->subjectRepository,
        );
    }

    public function testGetSubjectReturnsNullWhenSubjectIdIsNull(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $data = $this->createDossierRequestDto(subjectId: null);

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

        $this->expectExceptionObject(
            new ValidationException(
                ConstraintViolationBuilder::createList(
                    ConstraintViolationBuilder::forMissingEntity('subject', 'subjectId'),
                ),
            ),
        );

        $this->dossierSupportService->getSubject($data, $organisation);
    }

    public function testGetDepartmentReturnsDepartment(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $departmentId = Uuid::v6();
        $department = Mockery::mock(Department::class);

        $this->departmentRepository->expects('findByOrganisationAndId')
            ->with($organisation, $departmentId)
            ->andReturn($department);

        $result = $this->dossierSupportService->getDepartment($organisation, $departmentId);

        self::assertSame($department, $result);
    }

    public function testGetDepartmentThrowsWhenNotFound(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $departmentId = Uuid::v6();

        $this->departmentRepository->expects('findByOrganisationAndId')
            ->with($organisation, $departmentId)
            ->andReturnNull();

        try {
            $this->dossierSupportService->getDepartment($organisation, $departmentId);
        } catch (ValidationException $exception) {
            $violation = $exception->getConstraintViolationList()->get(0);

            self::assertEquals(ConstraintViolationBuilder::ENTITY_MISSING_ERROR, $violation->getCode());
            self::assertEquals('departmentId', $violation->getPropertyPath());
        }
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
                    title: DossierTitle::create('Title'),
                );
            }
        };
    }
}
