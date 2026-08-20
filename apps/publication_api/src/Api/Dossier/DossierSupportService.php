<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\DossierService;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Uid\Uuid;

final readonly class DossierSupportService
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private DossierDispatcher $dossierDispatcher,
        private DossierService $dossierService,
        private SubjectRepository $subjectRepository,
    ) {
    }

    public function getSubject(AbstractDossierRequestDto $data, Organisation $organisation): ?Subject
    {
        if ($data->subjectId === null) {
            return null;
        }

        $subject = $this->subjectRepository->findByOrganisationAndId($organisation, $data->subjectId);
        if (! $subject instanceof Subject) {
            throw new ValidationException(
                ConstraintViolationBuilder::createList(
                    ConstraintViolationBuilder::forMissingEntity('subject', 'subjectId'),
                ),
            );
        }

        return $subject;
    }

    public function getDepartment(Organisation $organisation, Uuid $departmentId): Department
    {
        $department = $this->departmentRepository->findByOrganisationAndId($organisation, $departmentId);
        if (! $department instanceof Department) {
            throw new ValidationException(
                ConstraintViolationBuilder::createList(
                    ConstraintViolationBuilder::forMissingEntity('department', 'departmentId'),
                ),
            );
        }

        return $department;
    }

    /**
     * @param array<array-key,AbstractAttachment> $attachments
     */
    public function addAttachments(EntityWithAttachments $entityWithAttachments, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $entityWithAttachments->addAttachment($attachment);
        }
    }

    public function synchronizeArtifacts(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchSynchronizeArtifactsCommand($dossier);
    }

    public function autoPublish(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierPublicationCommand($dossier);
    }

    public function validateCompletionAndPersist(AbstractDossier $dossier): void
    {
        $this->dossierService->validateCompletion($dossier);
    }
}
