<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<AbstractDossierRequestDto,?DossierDtoInterface>
 */
abstract class AbstractDossierProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        private readonly DepartmentRepository $departmentRepository,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly DossierService $dossierService,
        private readonly MainDocumentService $mainDocumentService,
        private readonly OrganisationRepository $organisationRepository,
        private readonly SubjectRepository $subjectRepository,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    protected function getOrganisation(array $uriVariables): Organisation
    {
        Assert::keyExists($uriVariables, 'organisationId');
        $organisationId = $uriVariables['organisationId'];

        Assert::isInstanceOf($organisationId, Uuid::class);

        $organisation = $this->organisationRepository->find($organisationId);
        Assert::isInstanceOf($organisation, Organisation::class);

        return $organisation;
    }

    protected function getSubject(AbstractDossierRequestDto $data, Organisation $organisation): ?Subject
    {
        if ($data->subjectId === null) {
            return null;
        }

        $subject = $this->subjectRepository->findByOrganisationAndId($organisation, $data->subjectId);
        Assert::isInstanceOf($subject, Subject::class);

        return $subject;
    }

    protected function getDepartment(Organisation $organisation, Uuid $departmentId): Department
    {
        return $this->departmentRepository->findByOrganisationAndId($organisation, $departmentId);
    }

    /**
     * @param array<array-key,AbstractAttachment> $attachments
     */
    protected function addAttachments(EntityWithAttachments $entityWithAttachments, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $entityWithAttachments->addAttachment($attachment);
        }
    }

    protected function removeDossierAttachments(EntityWithAttachments $entityWithAttachments): void
    {
        foreach ($entityWithAttachments->getAttachments() as $attachment) {
            $entityWithAttachments->removeAttachment($attachment);
        }
    }

    protected function dispatchCreateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchCreateDossierCommand($dossier);
    }

    protected function dispatchUpdateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierCommand($dossier);
    }

    protected function validateDossier(AbstractDossier $dossier): void
    {
        if (! $this->dossierService->isApiUpdateAllowed($dossier)) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('dossier update not allowed, in non-concept state'));
        }

        try {
            $this->dossierService->validate($dossier);
        } catch (ValidationFailedException $validationFailedException) {
            throw new ValidationException($validationFailedException->getViolations(), previous: $validationFailedException);
        }
    }

    /**
     * @param list<AbstractAttachment> $attachments
     */
    protected function validateAttachments(array $attachments): void
    {
        try {
            $this->attachmentService->validate($attachments);
        } catch (ValidationFailedException $validationFailedException) {
            $violations = $this->prefixViolationsPropertyPath(
                $validationFailedException->getViolations(),
                'attachments.'
            );
            throw new ValidationException($violations, previous: $validationFailedException);
        }
    }

    protected function validateMainDocument(AbstractMainDocument $mainDocument): void
    {
        try {
            $this->mainDocumentService->validate($mainDocument);
        } catch (ValidationFailedException $validationFailedException) {
            $violations = $this->prefixViolationsPropertyPath(
                $validationFailedException->getViolations(),
                'mainDocument.'
            );
            throw new ValidationException($violations, previous: $validationFailedException);
        }
    }

    protected function prefixViolationsPropertyPath(ConstraintViolationListInterface $violations, string $prefix): ConstraintViolationList
    {
        $constraintViolationList = new ConstraintViolationList();
        foreach ($violations as $violation) {
            $constraintViolation = new ConstraintViolation(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getRoot(),
                $prefix . $violation->getPropertyPath(),
                $violation->getInvalidValue(),
                $violation->getPlural(),
                $violation->getCode(),
                $violation->getConstraint(),
                $violation->getCause()
            );
            $constraintViolationList->add($constraintViolation);
        }

        return $constraintViolationList;
    }
}
