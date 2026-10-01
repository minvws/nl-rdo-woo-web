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
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Sitemap\SitemapDocumentSubscriber;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SitemapDocumentSubscriberTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierRepository&MockInterface $dossierRepository;
    private RouterInterface&MockInterface $router;
    private SitemapDocumentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->router = Mockery::mock(RouterInterface::class);

        $this->subscriber = new SitemapDocumentSubscriber(
            $this->entityManager,
            $this->dossierRepository,
            new DocumentCanonicalUrlGenerator(new PublicUrlGenerator('https://example.test', $this->router)),
        );
    }

    public function testPopulate(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getUpdatedAt')->andReturn($documentUpdatedAt = new DateTimeImmutable());
        $document->expects('getDocumentNumber')->twice()->andReturn($documentNumber = DocumentNumber::fromString('doc-123'));

        $dossierA = Mockery::mock(WooDecision::class);
        $dossierA->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $dossierB = Mockery::mock(WooDecision::class);
        $dossierB->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')->andReturn([
            $dossierA,
            $dossierB,
        ]);

        $urlContainer = Mockery::mock(UrlContainerInterface::class);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('select')->andReturnSelf();
        $queryBuilder->expects('where')->andReturnSelf();
        $queryBuilder->expects('andWhere')->andReturnSelf();
        $queryBuilder->expects('setParameter')->andReturnSelf();
        $queryBuilder->expects('setParameter')->andReturnSelf();
        $queryBuilder->expects('getQuery')->andReturn($query);

        $this->dossierRepository
            ->expects('createQueryBuilder')
            ->andReturn($queryBuilder);

        $this->router->expects('generate')
            ->with('app_document_canonical', ['documentNumber' => 'doc-123'])
            ->andReturn('/document/doc-123');

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);

        $urlContainer->expects('addUrl')
            ->once()
            ->with(
                Mockery::on(
                    static function (UrlConcrete $urlConcrete) use ($documentUpdatedAt): bool {
                        self::assertSame('https://example.test/document/doc-123', $urlConcrete->getLoc());
                        self::assertEquals($documentUpdatedAt, $urlConcrete->getLastmod());

                        return true;
                    },
                ),
                'documents',
            );

        $this->entityManager->expects('detach')->twice()->with($document);
        $this->entityManager->expects('detach')->with($dossierA);
        $this->entityManager->expects('detach')->with($dossierB);

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->__invoke($event);
    }
}
