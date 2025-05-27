<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class AttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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

        /** @var non-empty-list<Proxy<AnnualReportAttachment>> $attachments */
        $attachments = WooIndexAnnualReportStory::getPool('attachments');

        $expectedAttachmentUuids = array_map(
            fn (Proxy $attachment): string => $attachment->_real()->getId()->toRfc4122(),
            $attachments,
        );

        $this->assertCount(3, $allAttachments);
        foreach ($allAttachments as $attachment) {
            $this->assertContains($attachment->getId()->toRfc4122(), $expectedAttachmentUuids);
        }
    }
}
