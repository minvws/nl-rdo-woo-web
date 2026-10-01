<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentSynchronizer;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Tests\Unit\UnitTestCase;

final class DraftDecisionAttachmentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $draftDecisionAttachmentRequestDto = new DraftDecisionAttachmentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionAttachment::getAllowedTypes()),
            $this->getFaker()->externalId(),
        );
        $attachmentRequestDtos = [$draftDecisionAttachmentRequestDto->toAttachmentRequestDto()];

        $draftDecision = new DraftDecision();
        $draftDecision->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($draftDecision, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $draftDecisionAttachmentSynchronizer = new DraftDecisionAttachmentSynchronizer(
            $attachmentSynchronizer,
            $dossierAttachmentValidator,
        );

        self::assertSame(
            $attachmentEvents,
            $draftDecisionAttachmentSynchronizer->create($draftDecision, [$draftDecisionAttachmentRequestDto]),
        );
    }

    public function testUpdate(): void
    {
        $draftDecisionAttachmentRequestDto = new DraftDecisionAttachmentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionAttachment::getAllowedTypes()),
            $this->getFaker()->externalId(),
        );
        $attachmentRequestDtos = [$draftDecisionAttachmentRequestDto->toAttachmentRequestDto()];

        $draftDecision = new DraftDecision();
        $draftDecision->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($draftDecision, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($draftDecision, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $draftDecisionAttachmentSynchronizer = new DraftDecisionAttachmentSynchronizer(
            $attachmentSynchronizer,
            $dossierAttachmentValidator,
        );

        self::assertSame(
            $attachmentEvents,
            $draftDecisionAttachmentSynchronizer->update($draftDecision, [$draftDecisionAttachmentRequestDto]),
        );
    }
}
