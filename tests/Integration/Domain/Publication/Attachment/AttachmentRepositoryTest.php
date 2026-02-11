<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Attachment;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Zenstruck\Foundry\Attribute\WithStory;

use function array_map;
use function iterator_to_array;

final class AttachmentRepositoryTest extends SharedWebTestCase
{
    private AttachmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(AttachmentRepository::class);
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testGetPublishedAttachmentsIterable(): void
    {
        $iterable = $this->repository->getPublishedAttachmentsIterable();

        /** @var list<AbstractAttachment> $allAttachments */
        $allAttachments = iterator_to_array($iterable, false);

        /** @var non-empty-list<AnnualReportAttachment> $attachments */
        $attachments = WooIndexAnnualReportStory::getPool('attachments');

        $expectedAttachmentUuids = array_map(
            fn (AbstractAttachment $attachment): string => $attachment->getId()->toRfc4122(),
            $attachments,
        );

        $this->assertCount(3, $allAttachments);
        foreach ($allAttachments as $attachment) {
            $this->assertContains($attachment->getId()->toRfc4122(), $expectedAttachmentUuids);
        }
    }
}
