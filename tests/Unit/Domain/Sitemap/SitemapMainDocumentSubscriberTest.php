<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Sitemap\SitemapMainDocumentSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapMainDocumentSubscriberTest extends UnitTestCase
{
    public function testPopulate(): void
    {
        $updatedAt = new DateTimeImmutable();
        $url = $this->getFaker()->url();

        $dossier = Mockery::mock(WooDecision::class);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument->expects('getDossier')->andReturn($dossier);
        $mainDocument->expects('getUpdatedAt')->andReturn($updatedAt);

        $mainDocumentRepository = Mockery::mock(MainDocumentRepository::class);
        $mainDocumentRepository
            ->expects('getAllPublishedQuery->toIterable')
            ->andReturn([$mainDocument]);

        $dossierUrlGenerator = Mockery::mock(DossierUrlGenerator::class);
        $dossierUrlGenerator
            ->expects('documentDetailUrl')
            ->with($dossier)
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
            'main_documents',
        );

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('detach')->with($mainDocument);

        $subscriber = new SitemapMainDocumentSubscriber(
            $entityManager,
            $mainDocumentRepository,
            $dossierUrlGenerator,
        );

        $subscriber->__invoke(new SitemapPopulateEvent(
            $urlContainer,
            Mockery::mock(UrlGeneratorInterface::class),
        ));
    }
}
