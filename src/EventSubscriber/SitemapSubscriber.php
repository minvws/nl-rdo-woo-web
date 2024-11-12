<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierRepository $dossierRepository,
        private DossierPathHelper $dossierPathHelper,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => ['populate', 0],
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $this->populateDossiers($event->getUrlContainer());
        $this->populateDocuments($event->getUrlContainer(), $event->getUrlGenerator());
    }

    private function populateDossiers(UrlContainerInterface $urls): void
    {
        $dossierQuery = $this->dossierRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
        ;
        foreach ($dossierQuery->toIterable() as $dossier) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->dossierPathHelper->getAbsoluteDetailsPath($dossier),
                    $dossier->getUpdatedAt(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'dossiers',
            );
            $this->doctrine->detach($dossier);
        }
    }

    private function populateDocuments(UrlContainerInterface $urls, UrlGeneratorInterface $generator): void
    {
        $dossierQuery = $this->dossierRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->andWhere('d INSTANCE OF :type')
            ->setParameter('status', 'published')
            ->setParameter('type', DossierType::WOO_DECISION)
            ->getQuery()
        ;

        /** @var WooDecision $dossier */
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
                $this->doctrine->detach($document);
            }

            $this->doctrine->detach($dossier);
        }
    }
}
