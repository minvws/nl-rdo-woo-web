<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use App\Entity\Document;
use App\EventSubscriber\SitemapSubscriber;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapSubscriberTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierRepository&MockInterface $dossierRepository;
    private DossierPathHelper&MockInterface $dossierPathHelper;
    private SitemapSubscriber $subscriber;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->dossierPathHelper = \Mockery::mock(DossierPathHelper::class);
        $this->subscriber = new SitemapSubscriber(
            $this->entityManager,
            $this->dossierRepository,
            $this->dossierPathHelper,
        );
    }

    public function testPopulate(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('getUpdatedAt')->andReturn($documentUpdatedAt = new \DateTimeImmutable());
        $document->expects('getDocumentNr')->andReturn($documentNr = 'doc-123');

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getUpdatedAt')->andReturn($updatedAt = new \DateTimeImmutable());
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$document]));
        $dossier->expects('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'bar');

        $query = \Mockery::mock(Query::class);
        $query->expects('toIterable')->twice()->andReturn([
            $dossier,
        ]);

        $this->dossierRepository
            ->expects('createQueryBuilder->select->where->setParameter->getQuery')
            ->once()
            ->andReturn($query);

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

        $this->dossierRepository
            ->expects('createQueryBuilder->select->where->andWhere->setParameter->setParameter->getQuery')
            ->once()
            ->andReturn($query);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
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
            \Mockery::on(
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
