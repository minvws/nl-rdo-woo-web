<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Dossier;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Domain\Dossier\AttachmentMapper;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;

final class AttachmentMapperTest extends UnitTestCase
{
    public function testUpdateFromRequestDtoAppliesTheMetadata(): void
    {
        $attachment = $this->createUploadedAttachment();

        AttachmentMapper::updateFromRequestDto($attachment, new AttachmentRequestDto(
            fileName: FileName::create('renamed.pdf'),
            formalDate: PlainDate::create('2025-02-02'),
            language: AttachmentLanguage::ENG,
            type: AttachmentType::POLICY_NOTE,
            externalId: ExternalId::create('ext-123'),
            grounds: ['5.1.1a'],
        ));

        self::assertEquals(PlainDate::create('2025-02-02'), $attachment->getFormalDate());
        self::assertSame(AttachmentLanguage::ENG, $attachment->getLanguage());
        self::assertSame(AttachmentType::POLICY_NOTE, $attachment->getType());
        self::assertSame(['5.1.1a'], $attachment->getGrounds());
        self::assertSame('renamed.pdf', $attachment->getFileInfo()->getName());
    }

    public function testUpdateFromRequestDtoKeepsTheFile(): void
    {
        $attachment = $this->createUploadedAttachment();

        AttachmentMapper::updateFromRequestDto($attachment, new AttachmentRequestDto(
            fileName: FileName::create('original.pdf'),
            formalDate: PlainDate::create('2025-02-02'),
            language: AttachmentLanguage::NLD,
            type: AttachmentType::ADVICE,
            externalId: ExternalId::create('ext-123'),
        ));

        $fileInfo = $attachment->getFileInfo();
        self::assertTrue($fileInfo->isUploaded());
        self::assertSame('hash-of-original', $fileInfo->getHash());
        self::assertSame('/path/to/original.pdf', $fileInfo->getPath());
        self::assertSame(1234, $fileInfo->getSize());
        self::assertSame('application/pdf', $fileInfo->getMimetype());
    }

    public function testUpdateFromRequestDtoKeepsTheFileWhenTheFileNameChanges(): void
    {
        $attachment = $this->createUploadedAttachment();

        AttachmentMapper::updateFromRequestDto($attachment, new AttachmentRequestDto(
            fileName: FileName::create('renamed.pdf'),
            formalDate: PlainDate::create('2025-01-01'),
            language: AttachmentLanguage::NLD,
            type: AttachmentType::ADVICE,
            externalId: ExternalId::create('ext-123'),
        ));

        self::assertTrue($attachment->getFileInfo()->isUploaded());
        self::assertSame('hash-of-original', $attachment->getFileInfo()->getHash());
    }

    private function createUploadedAttachment(): CovenantAttachment
    {
        $attachment = new CovenantAttachment(
            Mockery::mock(Covenant::class),
            PlainDate::create('2025-01-01'),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        $fileInfo = $attachment->getFileInfo();
        $fileInfo->setName('original.pdf');
        $fileInfo->setUploaded(true);
        $fileInfo->setHash('hash-of-original');
        $fileInfo->setPath('/path/to/original.pdf');
        $fileInfo->setSize(1234);
        $fileInfo->setMimetype('application/pdf');

        return $attachment;
    }
}
