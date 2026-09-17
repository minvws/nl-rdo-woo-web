<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Dossier;

use Mockery;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;

final class MetadataSnapshotTest extends UnitTestCase
{
    public function testUnchangedEntityIsEqual(): void
    {
        $attachment = $this->createAttachment();
        $snapshot = MetadataSnapshot::of($attachment);

        self::assertTrue($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testMetadataChangeIsDetected(): void
    {
        $attachment = $this->createAttachment();
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->setFormalDate(PlainDate::create('2025-06-06'));

        self::assertFalse($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testFileNameChangeIsDetected(): void
    {
        $attachment = $this->createAttachment();
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->getFileInfo()->setName('renamed.pdf');

        self::assertFalse($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testFileContentChangeIsNotAMetadataChange(): void
    {
        $attachment = $this->createAttachment();
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->getFileInfo()->setHash('a-new-hash');
        $attachment->getFileInfo()->setSize(9999);

        self::assertTrue($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testInternalReferenceChangeIsNotAMetadataChange(): void
    {
        $attachment = $this->createAttachment();
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->setInternalReference('a new internal reference');

        self::assertTrue($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testReorderedGroundsAreNotAMetadataChange(): void
    {
        $attachment = $this->createAttachment();
        $attachment->setGrounds(['5.1.2e', '5.1.1a']);
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->setGrounds(['5.1.1a', '5.1.2e']);

        self::assertTrue($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testAddedGroundIsDetected(): void
    {
        $attachment = $this->createAttachment();
        $attachment->setGrounds(['5.1.1a']);
        $snapshot = MetadataSnapshot::of($attachment);

        $attachment->setGrounds(['5.1.1a', '5.1.2e']);

        self::assertFalse($snapshot->equalTo(MetadataSnapshot::of($attachment)));
    }

    public function testTwoDistinctEntitiesWithTheSameMetadataAreEqual(): void
    {
        self::assertTrue(
            MetadataSnapshot::of($this->createAttachment())
                ->equalTo(MetadataSnapshot::of($this->createAttachment())),
        );
    }

    public function testOfNullableReturnsNullForAnAbsentEntity(): void
    {
        self::assertNull(MetadataSnapshot::ofNullable(null));
        self::assertNotNull(MetadataSnapshot::ofNullable($this->createAttachment()));
    }

    private function createAttachment(): CovenantAttachment
    {
        $attachment = new CovenantAttachment(
            Mockery::mock(Covenant::class),
            PlainDate::create('2025-01-01'),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );
        $attachment->getFileInfo()->setName('original.pdf');

        return $attachment;
    }
}
