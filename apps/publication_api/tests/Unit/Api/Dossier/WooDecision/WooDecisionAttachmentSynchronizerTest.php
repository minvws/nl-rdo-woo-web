<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionAttachmentSynchronizer;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class WooDecisionAttachmentSynchronizerTest extends UnitTestCase
{
    public function testCreateAssertsUniqueExternalIds(): void
    {
        $attachmentRequestDtos = [
            new AttachmentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(),
                $this->getFaker()->externalId(),
            ),
        ];

        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($wooDecision, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $wooDecisionAttachmentSynchronizer = new WooDecisionAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $wooDecisionAttachmentSynchronizer->create($wooDecision, $attachmentRequestDtos));
    }

    public function testUpdateAlsoAssertsNoAttachmentRemovalIfPublished(): void
    {
        $attachmentRequestDtos = [
            new AttachmentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(),
                $this->getFaker()->externalId(),
            ),
        ];

        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($wooDecision, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($wooDecision, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $wooDecisionAttachmentSynchronizer = new WooDecisionAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $wooDecisionAttachmentSynchronizer->update($wooDecision, $attachmentRequestDtos));
    }
}
