<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceAttachmentSynchronizer;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Tests\Unit\UnitTestCase;

final class RequestForAdviceAttachmentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
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

        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($requestForAdvice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $requestForAdviceAttachmentSynchronizer = new RequestForAdviceAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $requestForAdviceAttachmentSynchronizer->create($requestForAdvice, $attachmentRequestDtos));
    }

    public function testUpdate(): void
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

        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($requestForAdvice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($requestForAdvice, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $requestForAdviceAttachmentSynchronizer = new RequestForAdviceAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $requestForAdviceAttachmentSynchronizer->update($requestForAdvice, $attachmentRequestDtos));
    }
}
