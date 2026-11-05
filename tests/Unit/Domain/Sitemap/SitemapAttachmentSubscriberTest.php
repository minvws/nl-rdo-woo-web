<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Sitemap\SitemapAttachmentSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class SitemapAttachmentSubscriberTest extends UnitTestCase
{
    public function testPopulate(): void
    {
        $attachmentId = Uuid::v6();
        $updatedAt = new DateTimeImmutable();
        $url = $this->getFaker()->url();

        $dossier = Mockery::mock(WooDecision::class);

        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getDossier')->andReturn($dossier);
        $attachment->expects('getId')->andReturn($attachmentId);
        $attachment->expects('getUpdatedAt')->andReturn($updatedAt);

        $attachmentRepository = Mockery::mock(AttachmentRepository::class);
        $attachmentRepository
            ->expects('getAllPublishedQuery->toIterable')
            ->andReturn([$attachment]);

        $dossierUrlGenerator = Mockery::mock(DossierUrlGenerator::class);
        $dossierUrlGenerator
            ->expects('attachmentDetailUrl')
            ->with($dossier, $attachmentId)
            ->andReturn(Url::create($url));

        $urlContainer = Mockery::mock(UrlContainerInterface::class);
        $urlContainer->expects('addUrl')->with(
            Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($url, $updatedAt): bool {
                    self::assertEquals($url, $urlConcrete->getLoc());
                    self::assertEquals($updatedAt, $urlConcrete->getLastmod());

                    return true;
                },
            ),
            'attachments',
        );

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('detach')->with($attachment);

        $subscriber = new SitemapAttachmentSubscriber(
            $entityManager,
            $attachmentRepository,
            $dossierUrlGenerator,
        );

        $subscriber->__invoke(new SitemapPopulateEvent(
            $urlContainer,
            Mockery::mock(UrlGeneratorInterface::class),
        ));
    }
}
