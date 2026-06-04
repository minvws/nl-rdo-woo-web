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
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Shared\ValueObject\ExternalId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

use function array_map;
use function array_unique;
use function count;

final readonly class DossierSupportService
{
    public function __construct(
        private AttachmentService $attachmentService,
        private DepartmentRepository $departmentRepository,
        private DossierDispatcher $dossierDispatcher,
        private DossierService $dossierService,
        private MainDocumentService $mainDocumentService,
        private SubjectRepository $subjectRepository,
        private Security $security,
    ) {
    }

    public function getSubject(AbstractDossierRequestDto $data, Organisation $organisation): ?Subject
    {
        if ($data->subjectId === null) {
            return null;
        }

        $subject = $this->subjectRepository->findByOrganisationAndId($organisation, $data->subjectId);
        Assert::isInstanceOf($subject, Subject::class);

        return $subject;
    }

    public function getDepartment(Organisation $organisation, Uuid $departmentId): Department
    {
        return $this->departmentRepository->findByOrganisationAndId($organisation, $departmentId);
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

    public function dispatchCreateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchCreateDossierCommand($dossier);
    }

    public function dispatchUpdateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierCommand($dossier);
    }

    public function dispatchUpdateDossierDetailsCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierDetailsCommand($dossier);
    }

    public function validateDossier(AbstractDossier $dossier): void
    {
        if (! $this->security->isGranted('AuthMatrix.dossier.update', $dossier)) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('dossier update is not allowed in non-concept state'));
        }

        try {
            $this->dossierService->validate($dossier, [
                DossierValidationGroup::DETAILS,
                DossierValidationGroup::DECISION,
                DossierValidationGroup::DOCUMENTS,
                DossierValidationGroup::PUBLICATION,
                DossierValidationGroup::CONTENT,
            ]);
        } catch (ValidationFailedException $validationFailedException) {
            $this->dossierService->refreshDossier($dossier);
            throw new ValidationException($validationFailedException->getViolations(), previous: $validationFailedException);
        }
    }

    /**
     * @param list<AbstractAttachment> $attachments
     */
    public function validateAttachments(array $attachments): void
    {
        $this->assertUniqueExternalIds($attachments);

        try {
            $this->attachmentService->validate($attachments);
        } catch (ValidationFailedException $validationFailedException) {
            $this->attachmentService->refreshAttachments($attachments);

            throw new ValidationException(
                $this->prefixViolationsPropertyPath($validationFailedException->getViolations(), 'attachments.'),
                previous: $validationFailedException,
            );
        }
    }

    /**
     * @param list<AbstractAttachment> $attachments
     */
    private function assertUniqueExternalIds(array $attachments): void
    {
        $externalIds = array_map(
            static function (AbstractAttachment $abstractAttachment) {
                $externalId = $abstractAttachment->getExternalId();
                Assert::isInstanceOf($externalId, ExternalId::class);

                return $externalId->__toString();
            },
            $attachments,
        );

        if (count($externalIds) === count(array_unique($externalIds))) {
            return;
        }

        throw new ValidationException(new ConstraintViolationList([
            new ConstraintViolation(
                'attachments contain non-unique external-ids',
                null,
                [],
                $attachments,
                'attachments',
                $externalIds,
                null,
                Unique::IS_NOT_UNIQUE,
            ),
        ]));
    }

    public function validateMainDocument(AbstractMainDocument $mainDocument): void
    {
        try {
            $this->mainDocumentService->validate($mainDocument);
        } catch (ValidationFailedException $validationFailedException) {
            $this->mainDocumentService->refreshMainDocument($mainDocument);

            $violations = $this->prefixViolationsPropertyPath(
                $validationFailedException->getViolations(),
                'mainDocument.',
            );

            throw new ValidationException($violations, previous: $validationFailedException);
        }
    }

    public function prefixViolationsPropertyPath(ConstraintViolationListInterface $violations, string $prefix): ConstraintViolationList
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
                $violation->getCause(),
            );
            $constraintViolationList->add($constraintViolation);
        }

        return $constraintViolationList;
    }
}
