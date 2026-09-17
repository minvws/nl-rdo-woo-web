<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;

use function array_filter;

final class AttachmentSynchronizerTest extends UnitTestCase
{
    private AttachmentSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->synchronizer = new AttachmentSynchronizer();
    }

    public function testUnchangedAttachmentYieldsNoEvent(): void
    {
        $covenant = new Covenant();
        $attachment = $this->addAttachment($covenant, 'ext-1', 'original.pdf');

        $events = $this->synchronizer->sync($covenant, [$this->createDto('ext-1', 'original.pdf')]);

        self::assertSame([], $events);
        self::assertTrue($covenant->getAttachments()->contains($attachment));
    }

    public function testChangedAttachmentYieldsAMetadataUpdatedEvent(): void
    {
        $covenant = new Covenant();
        $attachment = $this->addAttachment($covenant, 'ext-1', 'original.pdf');

        $events = $this->synchronizer->sync($covenant, [
            $this->createDto('ext-1', 'original.pdf', PlainDate::create('2025-06-06')),
        ]);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(AttachmentUpdatedEvent::class, $event);
        self::assertTrue($event->metadataUpdated);
        self::assertFalse($event->fileUpdated);
        self::assertSame($attachment->getId(), $event->attachmentId);
    }

    public function testRemovedAttachmentYieldsADeletedEventCarryingItsFileDetails(): void
    {
        $covenant = new Covenant();
        $attachment = $this->addAttachment($covenant, 'ext-1', 'gone.pdf');
        $attachment->getFileInfo()->setType('pdf');

        $events = $this->synchronizer->sync($covenant, []);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(AttachmentDeletedEvent::class, $event);
        self::assertSame($attachment->getId(), $event->attachmentId);
        self::assertSame('gone.pdf', $event->fileName);
        self::assertSame('pdf', $event->fileType);
        self::assertCount(0, $covenant->getAttachments());
    }

    public function testNewAttachmentYieldsNoEvent(): void
    {
        $covenant = new Covenant();

        $events = $this->synchronizer->sync($covenant, [$this->createDto('ext-new', 'new.pdf')]);

        self::assertSame([], $events);
        self::assertCount(1, $covenant->getAttachments());
    }

    public function testMixedChangesYieldOneEventPerAffectedAttachment(): void
    {
        $covenant = new Covenant();
        $this->addAttachment($covenant, 'ext-keep', 'keep.pdf');
        $this->addAttachment($covenant, 'ext-change', 'change.pdf');
        $this->addAttachment($covenant, 'ext-remove', 'remove.pdf');

        $events = $this->synchronizer->sync($covenant, [
            $this->createDto('ext-keep', 'keep.pdf'),
            $this->createDto('ext-change', 'change.pdf', PlainDate::create('2025-06-06')),
            $this->createDto('ext-add', 'add.pdf'),
        ]);

        self::assertCount(2, $events);
        self::assertCount(1, array_filter($events, static fn ($e): bool => $e instanceof AttachmentUpdatedEvent));
        self::assertCount(1, array_filter($events, static fn ($e): bool => $e instanceof AttachmentDeletedEvent));
        self::assertCount(3, $covenant->getAttachments());
    }

    private function addAttachment(Covenant $covenant, string $externalId, string $fileName): CovenantAttachment
    {
        $attachment = new CovenantAttachment(
            $covenant,
            PlainDate::create('2025-01-01'),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );
        $attachment->setExternalId(ExternalId::create($externalId));
        $attachment->getFileInfo()->setName($fileName);
        $covenant->addAttachment($attachment);

        return $attachment;
    }

    private function createDto(
        string $externalId,
        string $fileName,
        ?PlainDate $formalDate = null,
    ): AttachmentRequestDto {
        return new AttachmentRequestDto(
            fileName: FileName::create($fileName),
            formalDate: $formalDate ?? PlainDate::create('2025-01-01'),
            language: AttachmentLanguage::NLD,
            type: AttachmentType::ADVICE,
            externalId: ExternalId::create($externalId),
        );
    }
}
