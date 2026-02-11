<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Sitemap\SitemapDocumentSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapDocumentSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierRepository&MockInterface $dossierRepository;
    private SitemapDocumentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->dossierRepository = Mockery::mock(DossierRepository::class);

        $this->subscriber = new SitemapDocumentSubscriber(
            $this->entityManager,
            $this->dossierRepository,
        );
    }

    public function testPopulate(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getUpdatedAt')->andReturn($documentUpdatedAt = new DateTimeImmutable());
        $document->expects('getDocumentNr')->andReturn($documentNr = 'doc-123');

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$document]));
        $dossier->expects('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'bar');

        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')->andReturn([
            $dossier,
        ]);

        $urlContainer = Mockery::mock(UrlContainerInterface::class);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('andWhere')->andReturnSelf();
        $queryBuilder->shouldReceive('setParameter')->andReturnSelf();
        $queryBuilder->shouldReceive('setParameter')->andReturnSelf();
        $queryBuilder->shouldReceive('getQuery')->andReturn($query);

        $this->dossierRepository
            ->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder);

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_document_detail',
            [
                'prefix' => $prefix,
                'dossierId' => $dossierNr,
                'documentId' => $documentNr,
            ],
            0,
        )->andReturn($docUrl = '/foo/bar/doc-123');

        $urlContainer->expects('addUrl')->with(
            Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($docUrl, $documentUpdatedAt): bool {
                    self::assertEquals($docUrl, $urlConcrete->getLoc());
                    self::assertEquals($documentUpdatedAt, $urlConcrete->getLastmod());

                    return true;
                }
            ),
            'documents',
        );

        $this->entityManager->expects('detach')->with($document);
        $this->entityManager->expects('detach')->with($dossier);

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
