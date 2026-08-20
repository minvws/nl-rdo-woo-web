<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\AttachmentService;
use Shared\Service\EnumHelper;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

use function array_diff;
use function array_map;
use function array_unique;
use function count;
use function in_array;
use function sort;

final readonly class DossierAttachmentValidator
{
    public function __construct(
        private AttachmentService $attachmentService,
    ) {
    }

    /**
     * @param list<AbstractAttachment> $attachments
     */
    public function validate(array $attachments, DossierStatus $dossierStatus): void
    {
        $validationGroups = EnumHelper::getStringValues(
            DossierValidationGroup::getForStatus($dossierStatus),
        );
        $validationGroups[] = Constraint::DEFAULT_GROUP;

        try {
            $this->attachmentService->validate($attachments, $validationGroups);
        } catch (ValidationFailedException $validationFailedException) {
            $this->attachmentService->refreshAttachments($attachments);

            throw new ValidationException(
                ConstraintViolationBuilder::prefixPropertyPaths($validationFailedException->getViolations(), 'attachments.'),
                previous: $validationFailedException,
            );
        }
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     */
    public function assertAttachmentSetUnchangedInNonConcept(AbstractDossier&EntityWithAttachments $dossier, array $attachmentRequestDtos): void
    {
        if (! in_array($dossier->getStatus(), DossierStatus::nonConceptCases(), true)) {
            return;
        }

        $existingExternalIds = [];
        foreach ($dossier->getAttachments() as $attachment) {
            $externalId = $attachment->getExternalId();
            Assert::isInstanceOf($externalId, ExternalId::class);

            $existingExternalIds[] = $externalId->toString();
        }

        $incomingExternalIds = $this->getExternalIdsAsStrings($attachmentRequestDtos);

        sort($existingExternalIds);
        sort($incomingExternalIds);

        if ($existingExternalIds !== $incomingExternalIds) {
            throw new ValidationException(
                ConstraintViolationBuilder::createList(ConstraintViolationBuilder::forModifiedSubEntity('attachments')),
            );
        }
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     */
    public function assertNoAttachmentRemovalInNonConcept(AbstractDossier&EntityWithAttachments $dossier, array $attachmentRequestDtos): void
    {
        if (! in_array($dossier->getStatus(), DossierStatus::nonConceptCases(), true)) {
            return;
        }

        $existingExternalIds = [];
        foreach ($dossier->getAttachments() as $attachment) {
            $externalId = $attachment->getExternalId();
            Assert::isInstanceOf($externalId, ExternalId::class);

            $existingExternalIds[] = $externalId->toString();
        }

        $incomingExternalIds = $this->getExternalIdsAsStrings($attachmentRequestDtos);

        if (array_diff($existingExternalIds, $incomingExternalIds) !== []) {
            throw new ValidationException(
                ConstraintViolationBuilder::createList(ConstraintViolationBuilder::forModifiedSubEntity('attachments')),
            );
        }
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     */
    public function assertUniqueExternalIds(array $attachmentRequestDtos): void
    {
        $externalIds = $this->getExternalIdsAsStrings($attachmentRequestDtos);

        if (count($externalIds) === count(array_unique($externalIds))) {
            return;
        }

        throw new ValidationException(new ConstraintViolationList([
            new ConstraintViolation(
                'attachments contain non-unique external-ids',
                null,
                [],
                $attachmentRequestDtos,
                'attachments',
                $externalIds,
                null,
                Unique::IS_NOT_UNIQUE,
            ),
        ]));
    }

    /**
     * @param array<array-key, AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return array<array-key, string>
     */
    public function getExternalIdsAsStrings(array $attachmentRequestDtos): array
    {
        return array_map(
            static function (AttachmentRequestDto $attachmentRequestDto): string {
                return $attachmentRequestDto->externalId->toString();
            },
            $attachmentRequestDtos,
        );
    }
}
