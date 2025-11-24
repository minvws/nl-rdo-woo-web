<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Sitemap\SitemapDossierSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapDossierSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierRepository&MockInterface $dossierRepository;
    private DossierPathHelper&MockInterface $dossierPathHelper;
    private SitemapDossierSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->dossierPathHelper = \Mockery::mock(DossierPathHelper::class);
        $this->subscriber = new SitemapDossierSubscriber(
            $this->entityManager,
            $this->dossierRepository,
            $this->dossierPathHelper,
        );
    }

    public function testPopulate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getUpdatedAt')->andReturn($updatedAt = new \DateTimeImmutable());

        $query = \Mockery::mock(Query::class);
        $query->expects('toIterable')->andReturn([
            $dossier,
        ]);

        $queryBuilder = \Mockery::mock(QueryBuilder::class);
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('setParameter')->andReturnSelf();
        $queryBuilder->shouldReceive('getQuery')->andReturn($query);

        $this->dossierRepository
            ->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder);

        $this->dossierPathHelper->expects('getAbsoluteDetailsPath')->with($dossier)->andReturn($url = '/foo/bar');

        $urlContainer = \Mockery::mock(UrlContainerInterface::class);
        $urlContainer->expects('addUrl')->with(
            \Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($url, $updatedAt): bool {
                    self::assertEquals($url, $urlConcrete->getLoc());
                    self::assertEquals($updatedAt, $urlConcrete->getLastmod());

                    return true;
                }
            ),
            'dossiers',
        );

        $this->entityManager->expects('detach')->with($dossier);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
