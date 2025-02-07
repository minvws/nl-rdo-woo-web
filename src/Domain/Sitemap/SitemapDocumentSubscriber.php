<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapDocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierRepository $dossierRepository,
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
