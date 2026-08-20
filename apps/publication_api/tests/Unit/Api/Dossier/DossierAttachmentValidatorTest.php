<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\AttachmentService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\FileName;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function sprintf;

class DossierAttachmentValidatorTest extends UnitTestCase
{
    public function testValidateUsesValidationGroupsForGivenStatus(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $attachment1 = Mockery::mock(AbstractAttachment::class);
        $attachment2 = Mockery::mock(AbstractAttachment::class);

        $attachmentService->expects('validate')
            ->with(
                [$attachment1, $attachment2],
                [
                    DossierValidationGroup::DETAILS->value,
                    DossierValidationGroup::DECISION->value,
                    DossierValidationGroup::DOCUMENTS->value,
                    DossierValidationGroup::PUBLICATION->value,
                    DossierValidationGroup::CONTENT->value,
                    Constraint::DEFAULT_GROUP,
                ],
            );

        $dossierAttachmentValidator->validate([$attachment1, $attachment2], DossierStatus::CONCEPT);
    }

    public function testValidateAddsLockedValidationGroupForNonConceptStatus(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $attachment = Mockery::mock(AbstractAttachment::class);

        $attachmentService->expects('validate')
            ->with(
                [$attachment],
                [
                    DossierValidationGroup::DETAILS->value,
                    DossierValidationGroup::DECISION->value,
                    DossierValidationGroup::DOCUMENTS->value,
                    DossierValidationGroup::PUBLICATION->value,
                    DossierValidationGroup::CONTENT->value,
                    DossierValidationGroup::PUBLICATION_LOCKED->value,
                    Constraint::DEFAULT_GROUP,
                ],
            );

        $dossierAttachmentValidator->validate([$attachment], DossierStatus::PUBLISHED);
    }

    public function testValidateRefreshesAndRethrowsOnValidationFailure(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $message = self::getFaker()->sentence();
        $attachment = Mockery::mock(AbstractAttachment::class);
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                $message,
                null,
                [],
                null,
                'fileName',
                null,
                null,
                null,
            ),
        ]);
        $validationFailedException = new ValidationFailedException([$attachment], $violations);

        $attachmentService->expects('validate')
            ->andThrow($validationFailedException);

        $attachmentService->expects('refreshAttachments')
            ->with([$attachment]);

        self::expectException(ValidationException::class);
        self::expectExceptionMessageIs(sprintf('attachments.fileName: %s', $message));

        $dossierAttachmentValidator->validate([$attachment], DossierStatus::CONCEPT);
    }

    public function testAssertUniqueExternalIdsPassesWhenUnique(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $dossierAttachmentValidator->assertUniqueExternalIds([
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: self::getFaker()->externalId(),
            ),
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: self::getFaker()->externalId(),
            ),
        ]);

        $this->expectNotToPerformAssertions();
    }

    public function testAssertUniqueExternalIdsThrowsOnDuplicate(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $externalId = self::getFaker()->externalId();

        self::expectException(ValidationException::class);

        $dossierAttachmentValidator->assertUniqueExternalIds([
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: $externalId,
            ),
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: $externalId,
            ),
        ]);
    }

    public function testAssertAttachmentSetUnchangedInNonConceptPassesWhenAttachmentsAreUnchanged(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $externalId = self::getFaker()->externalId();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getExternalId')
            ->andReturn($externalId);

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getAttachments')
            ->andReturn(new ArrayCollection([$attachment]));

        $dossierAttachmentValidator->assertAttachmentSetUnchangedInNonConcept($dossier, [
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: $externalId,
            ),
        ]);
    }

    public function testAssertAttachmentSetUnchangedInNonConceptIsSkippedForConceptDossier(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);

        $dossierAttachmentValidator->assertAttachmentSetUnchangedInNonConcept($dossier, []);
    }

    public function testAssertAttachmentSetUnchangedInNonConceptThrowsWhenAttachmentIsRemoved(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getExternalId')
            ->andReturn(self::getFaker()->externalId());

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getAttachments')
            ->andReturn(new ArrayCollection([$attachment]));

        self::expectException(ValidationException::class);

        $dossierAttachmentValidator->assertAttachmentSetUnchangedInNonConcept($dossier, []);
    }

    public function testAssertNoAttachmentRemovalInNonConceptPassesWhenAttachmentIsAdded(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $externalId = self::getFaker()->externalId();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getExternalId')
            ->andReturn($externalId);

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getAttachments')
            ->andReturn(new ArrayCollection([$attachment]));

        $dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($dossier, [
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: $externalId,
            ),
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: self::getFaker()->externalId(),
            ),
        ]);

        $this->addToAssertionCount(1);
    }

    public function testAssertAttachmentSetUnchangedInNonConceptThrowsWhenAttachmentIsAdded(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $externalId = self::getFaker()->externalId();

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getExternalId')
            ->andReturn($externalId);

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getAttachments')
            ->andReturn(new ArrayCollection([$attachment]));

        self::expectException(ValidationException::class);

        $dossierAttachmentValidator->assertAttachmentSetUnchangedInNonConcept($dossier, [
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: $externalId,
            ),
            new AttachmentRequestDto(
                fileName: FileName::create(sprintf('%s.pdf', self::getFaker()->word())),
                formalDate: self::getFaker()->plainDate(),
                language: self::getFaker()->attachmentLanguage(),
                type: self::getFaker()->attachmentType(),
                externalId: self::getFaker()->externalId(),
            ),
        ]);
    }

    public function testAssertNoAttachmentRemovalInNonConceptThrowsWhenAttachmentIsRemoved(): void
    {
        $attachmentService = Mockery::mock(AttachmentService::class);
        $dossierAttachmentValidator = new DossierAttachmentValidator($attachmentService);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getExternalId')
            ->andReturn(self::getFaker()->externalId());

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getAttachments')
            ->andReturn(new ArrayCollection([$attachment]));

        self::expectException(ValidationException::class);

        $dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($dossier, []);
    }
}
