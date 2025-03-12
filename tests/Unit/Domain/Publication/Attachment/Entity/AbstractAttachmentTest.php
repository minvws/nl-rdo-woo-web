<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Entity;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AbstractAttachmentTest extends MockeryTestCase
{
    private Covenant&MockInterface $dossier;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(Covenant::class);
    }

    public function testCanWithdrawReturnsFalseWhenDossierIsConcept(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $attachment = new CovenantAttachment(
            $this->dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        self::assertFalse($attachment->canWithdraw());
    }

    public function testCanWithdrawReturnsFalseWhenFileIsNotUploaded(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = new CovenantAttachment(
            $this->dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        self::assertFalse($attachment->canWithdraw());
    }

    public function testCanWithdrawReturnsTrueWhenFileIsUploaded(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = new CovenantAttachment(
            $this->dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setUploaded(true);

        $attachment->setFileInfo($fileInfo);

        self::assertTrue($attachment->canWithdraw());
    }

    public function testCanWithdrawOnlyOnce(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = new CovenantAttachment(
            $this->dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setUploaded(true);

        $attachment->setFileInfo($fileInfo);

        self::assertFalse($attachment->isWithdrawn());
        self::assertTrue($attachment->canWithdraw());

        $attachment->withdraw(
            $reason = AttachmentWithdrawReason::INCOMPLETE,
            $explanation = 'foo bar',
        );

        self::assertTrue($attachment->isWithdrawn());
        self::assertFalse($attachment->canWithdraw());
        self::assertEquals($reason, $attachment->getWithdrawReason());
        self::assertEquals($explanation, $attachment->getWithdrawExplanation());

        $this->expectException(AttachmentWithdrawException::class);
        $attachment->withdraw(
            AttachmentWithdrawReason::UNRELATED,
            'bar foo',
        );
    }
}
