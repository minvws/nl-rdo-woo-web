<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
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

        $draftDecision = new DraftDecision();

        $attachmentEvents = [];

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($draftDecision, [$draftDecisionAttachmentRequestDto->toAttachmentRequestDto()])
            ->andReturn($attachmentEvents);

        $draftDecisionAttachmentSynchronizer = new DraftDecisionAttachmentSynchronizer($dossierAttachmentSynchronizer);

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

        $draftDecision = new DraftDecision();

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($draftDecision, [$draftDecisionAttachmentRequestDto->toAttachmentRequestDto()])
            ->andReturn($attachmentEvents);

        $draftDecisionAttachmentSynchronizer = new DraftDecisionAttachmentSynchronizer($dossierAttachmentSynchronizer);

        self::assertSame(
            $attachmentEvents,
            $draftDecisionAttachmentSynchronizer->update($draftDecision, [$draftDecisionAttachmentRequestDto]),
        );
    }
}
