<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierSupportServiceTest extends UnitTestCase
{
    private SubjectRepository&MockInterface $subjectRepository;
    private DepartmentRepository&MockInterface $departmentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierSupportService $dossierSupportService;

    protected function setUp(): void
    {
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);
        $this->departmentRepository = Mockery::mock(DepartmentRepository::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);

        $this->dossierSupportService = new DossierSupportService(
            $this->departmentRepository,
            Mockery::mock(DossierDispatcher::class),
            Mockery::mock(DossierService::class),
            $this->subjectRepository,
            $this->messageBus,
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

    public function testDispatchDossierCreatedEvent(): void
    {
        $covenant = new Covenant();

        $this->messageBus->expects('dispatch')
            ->withArgs(static function (DossierCreatedEvent $event) use ($covenant): bool {
                self::assertSame($covenant->getId(), $event->dossierId);

                return true;
            })
            ->andReturn(new Envelope(new stdClass()));

        $this->dossierSupportService->dispatchDossierCreatedEvent($covenant);
    }

    public function testDispatchPublicationEventsDispatchesTheAttachmentEvents(): void
    {
        $covenant = new Covenant();
        $event = $this->createAttachmentUpdatedEvent($covenant);

        $this->messageBus->expects('dispatch')->with($event)->andReturn(new Envelope(new stdClass()));

        $this->dossierSupportService->dispatchPublicationEvents($covenant, MetadataSnapshot::ofNullable(null), [$event]);
    }

    public function testDispatchPublicationEventsAddsAMainDocumentEventWhenItsMetadataChanged(): void
    {
        $covenant = new Covenant();
        $mainDocument = new CovenantMainDocument($covenant, PlainDate::create('2025-01-01'), AttachmentLanguage::NLD);
        $covenant->setMainDocument($mainDocument);

        $snapshot = MetadataSnapshot::of($mainDocument);
        $mainDocument->setFormalDate(PlainDate::create('2025-06-06'));

        $this->messageBus->expects('dispatch')
            ->withArgs(static function (MainDocumentUpdatedEvent $event) use ($mainDocument): bool {
                self::assertSame($mainDocument->getId(), $event->documentId);
                self::assertTrue($event->metadataUpdated);
                self::assertFalse($event->fileUpdated);

                return true;
            })
            ->andReturn(new Envelope(new stdClass()));

        $this->dossierSupportService->dispatchPublicationEvents($covenant, $snapshot, []);
    }

    public function testDispatchPublicationEventsDispatchesNothingWhenNothingChanged(): void
    {
        $covenant = new Covenant();
        $mainDocument = new CovenantMainDocument($covenant, PlainDate::create('2025-01-01'), AttachmentLanguage::NLD);
        $covenant->setMainDocument($mainDocument);

        $this->messageBus->expects('dispatch')->never();

        $this->dossierSupportService->dispatchPublicationEvents($covenant, MetadataSnapshot::of($mainDocument), []);
    }

    private function createAttachmentUpdatedEvent(Covenant $covenant): AttachmentUpdatedEvent
    {
        $attachment = new CovenantAttachment(
            $covenant,
            PlainDate::create('2025-01-01'),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        return AttachmentUpdatedEvent::forAttachmentWithMetadataUpdated($attachment);
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
