<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Tests\Unit\UnitTestCase;

final class AdviceAttachmentSynchronizerTest extends UnitTestCase
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

        $advice = new Advice();
        $advice->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($advice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $adviceAttachmentSynchronizer->create($advice, $attachmentRequestDtos));
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

        $advice = new Advice();
        $advice->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($advice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($advice, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $adviceAttachmentSynchronizer->update($advice, $attachmentRequestDtos));
    }

    public function testSynchronizeWithMoreThanOneRequestForAdviceAttachment(): void
    {
        $attachmentRequestDtos = [
            new AttachmentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                AttachmentType::REQUEST_FOR_ADVICE,
                $this->getFaker()->externalId(),
            ),
        ];

        $advice = new Advice();
        $advice->setStatus(DossierStatus::CONCEPT);
        $advice->addAttachment(
            new AdviceAttachment(
                $advice,
                $this->getFaker()->plainDate(),
                AttachmentType::REQUEST_FOR_ADVICE,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $advice->addAttachment(
            new AdviceAttachment(
                $advice,
                $this->getFaker()->plainDate(),
                AttachmentType::REQUEST_FOR_ADVICE,
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($advice, $attachmentRequestDtos)->andReturn([]);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::expectException(ValidationException::class);

        $adviceAttachmentSynchronizer->create($advice, $attachmentRequestDtos);
    }
}
