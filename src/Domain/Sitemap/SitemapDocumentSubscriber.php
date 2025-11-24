<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapDocumentSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierRepository $dossierRepository,
    ) {
    }

    #[AsEventListener(event: SitemapPopulateEvent::class)]
    public function populate(SitemapPopulateEvent $event): void
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
                $event->getUrlContainer()->addUrl(
                    new UrlConcrete(
                        $event->getUrlGenerator()->generate('app_document_detail', [
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
