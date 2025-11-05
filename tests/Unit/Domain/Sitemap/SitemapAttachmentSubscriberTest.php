<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sitemap;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Sitemap\SitemapAttachmentSubscriber;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class SitemapAttachmentSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AttachmentRepository&MockInterface $attachmentRepository;
    private SitemapAttachmentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);

        $this->subscriber = new SitemapAttachmentSubscriber(
            $this->entityManager,
            $this->attachmentRepository,
        );
    }

    public function testPopulate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = 'bar');
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getId')->andReturn($attachmentId = Uuid::v6());
        $attachment->shouldReceive('getUpdatedAt')->andReturn($updatedAt = new \DateTimeImmutable());

        $urlContainer = \Mockery::mock(UrlContainerInterface::class);

        $this->attachmentRepository
            ->expects('getAllPublishedQuery->toIterable')
            ->once()
            ->andReturn([$attachment]);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
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
            \Mockery::on(
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
