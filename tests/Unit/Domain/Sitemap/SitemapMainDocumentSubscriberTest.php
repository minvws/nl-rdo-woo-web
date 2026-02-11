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
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Sitemap\SitemapMainDocumentSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapMainDocumentSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private MainDocumentRepository&MockInterface $mainDocumentRepository;
    private SitemapMainDocumentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->mainDocumentRepository = Mockery::mock(MainDocumentRepository::class);

        $this->subscriber = new SitemapMainDocumentSubscriber(
            $this->entityManager,
            $this->mainDocumentRepository,
        );
    }

    public function testPopulate(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = 'bar');
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument->shouldReceive('getDossier')->andReturn($dossier);
        $mainDocument->shouldReceive('getUpdatedAt')->andReturn($updatedAt = new DateTimeImmutable());

        $urlContainer = Mockery::mock(UrlContainerInterface::class);

        $this->mainDocumentRepository
            ->expects('getAllPublishedQuery->toIterable')
            ->once()
            ->andReturn([$mainDocument]);

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_covenant_document_detail',
            [
                'prefix' => $prefix,
                'dossierId' => $dossierNr,
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
            'main_documents',
        );

        $this->entityManager->expects('detach')->with($mainDocument);

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
