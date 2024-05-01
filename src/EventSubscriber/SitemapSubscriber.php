<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapSubscriber implements EventSubscriberInterface
{
    protected DossierRepository $dossierRepository;
    protected DocumentRepository $documentRepository;
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine, DossierRepository $dossierRepository, DocumentRepository $documentRepository)
    {
        $this->dossierRepository = $dossierRepository;
        $this->documentRepository = $documentRepository;
        $this->doctrine = $doctrine;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => ['populate', 0],
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $this->populateDossiers($event->getUrlContainer(), $event->getUrlGenerator());
        $this->populateDocuments($event->getUrlContainer(), $event->getUrlGenerator());
    }

    protected function populateDossiers(UrlContainerInterface $urls, UrlGeneratorInterface $generator): void
    {
        $dossierQuery = $this->doctrine->getRepository(Dossier::class)->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
        ;

        foreach ($dossierQuery->toIterable() as $dossier) {
            $urls->addUrl(
                new UrlConcrete(
                    $generator->generate(
                        'app_woodecision_detail',
                        ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $dossier->getUpdatedAt(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'dossiers',
            );
            $this->doctrine->detach($dossier);
        }
    }

    protected function populateDocuments(UrlContainerInterface $urls, UrlGeneratorInterface $generator): void
    {
        $dossierQuery = $this->doctrine->getRepository(Dossier::class)->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
        ;

        foreach ($dossierQuery->toIterable() as $dossier) {
            foreach ($dossier->getDocuments() as $document) {
                $urls->addUrl(
                    new UrlConcrete(
                        $generator->generate('app_document_detail', [
                            'prefix' => $dossier->getDocumentPrefix(),
                            'dossierId' => $dossier->getDossierNr(),
                            'documentId' => $document->getDocumentNr(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        $document->getUpdatedAt(),
                        UrlConcrete::CHANGEFREQ_MONTHLY,
                        0.8
                    ),
                    'documents',
                );
            }

            $this->doctrine->detach($dossier);
        }
    }
}
