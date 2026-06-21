<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Attachment;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Zenstruck\Foundry\Attribute\WithStory;

use function array_map;
use function iterator_to_array;

final class AttachmentRepositoryTest extends SharedWebTestCase
{
    private AttachmentRepository $attachmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentRepository = self::fromContainer(AttachmentRepository::class);
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testGetPublishedAttachmentsIterable(): void
    {
        $iterable = $this->attachmentRepository->getPublishedAttachmentsIterable();

        /** @var list<AbstractAttachment> $allAttachments */
        $allAttachments = iterator_to_array($iterable, false);

        /** @var non-empty-list<AnnualReportAttachment> $attachments */
        $attachments = WooIndexAnnualReportStory::getPool('attachments');

        $expectedAttachmentUuids = array_map(
            static fn (AbstractAttachment $attachment): string => $attachment->getId()->toRfc4122(),
            $attachments,
        );

        $this->assertCount(3, $allAttachments);
        foreach ($allAttachments as $attachment) {
            $this->assertContains($attachment->getId()->toRfc4122(), $expectedAttachmentUuids);
        }
    }

    public function testHasIncompleteAttachmentsForDossierIsFalse(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
        ]);

        $this->assertFalse($this->attachmentRepository->hasIncompleteAttachmentsForDossier($wooDecision->getId()));
    }

    public function testHasIncompleteAttachmentsForDossierEmptyLanguage(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $attachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
        ]);

        $dbal = self::getContainer()->get('doctrine.dbal.default_connection');

        $dbal->executeStatement(
            'UPDATE attachment SET language = :language WHERE id = :id',
            [
                'language' => '',
                'id' => $attachment->getId()->toRfc4122(),
            ],
        );

        self::fromContainer(EntityManagerInterface::class)->clear();

        $this->assertTrue($this->attachmentRepository->hasIncompleteAttachmentsForDossier($wooDecision->getId()));
    }

    public function testHasIncompleteAttachmentsForDossier(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'fileInfo' => new FileInfo(),
        ]);

        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
        ]);

        $this->assertTrue($this->attachmentRepository->hasIncompleteAttachmentsForDossier($wooDecision->getId()));
    }
}
