<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
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

        $attachmentEvents = [];

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->with($advice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($dossierAttachmentSynchronizer);

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

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->with($advice, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($dossierAttachmentSynchronizer);

        self::assertSame($attachmentEvents, $adviceAttachmentSynchronizer->update($advice, $attachmentRequestDtos));
    }

    public function testCreateWithMultipleAttachmentsIsProhibited(): void
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

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->with($advice, $attachmentRequestDtos)->andReturn([]);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($dossierAttachmentSynchronizer);

        self::expectException(ValidationException::class);

        $adviceAttachmentSynchronizer->create($advice, $attachmentRequestDtos);
    }

    public function testUpdateWithMultipleAttachmentsIsProhibited(): void
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

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->with($advice, $attachmentRequestDtos)->andReturn([]);

        $adviceAttachmentSynchronizer = new AdviceAttachmentSynchronizer($dossierAttachmentSynchronizer);

        self::expectException(ValidationException::class);

        $adviceAttachmentSynchronizer->update($advice, $attachmentRequestDtos);
    }
}
