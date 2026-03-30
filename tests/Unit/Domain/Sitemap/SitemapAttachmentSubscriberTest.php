<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Sitemap\SitemapAttachmentSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class SitemapAttachmentSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AttachmentRepository&MockInterface $attachmentRepository;
    private SitemapAttachmentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->attachmentRepository = Mockery::mock(AttachmentRepository::class);

        $this->subscriber = new SitemapAttachmentSubscriber(
            $this->entityManager,
            $this->attachmentRepository,
        );
    }

    public function testPopulate(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'bar');
        $dossier->expects('getType')->andReturn(DossierType::COVENANT);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getDossier')->andReturn($dossier);
        $attachment->expects('getId')->andReturn($attachmentId = Uuid::v6());
        $attachment->expects('getUpdatedAt')->andReturn($updatedAt = new DateTimeImmutable());

        $urlContainer = Mockery::mock(UrlContainerInterface::class);

        $this->attachmentRepository
            ->expects('getAllPublishedQuery->toIterable')

            ->andReturn([$attachment]);

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_covenant_attachment_detail',
            [
                'prefix' => $prefix,
                'dossierId' => $dossierNr,
                'attachmentId' => $attachmentId,
            ],
            0,
        )->andReturn($attachmentUrl = '/foo/bar/attachment-123');

        $urlContainer->expects('addUrl')->with(
            Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($attachmentUrl, $updatedAt): bool {
                    self::assertEquals($attachmentUrl, $urlConcrete->getLoc());
                    self::assertEquals($updatedAt, $urlConcrete->getLastmod());

                    return true;
                }
            ),
            'attachments',
        );

        $this->entityManager->expects('detach')->with($attachment);

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
