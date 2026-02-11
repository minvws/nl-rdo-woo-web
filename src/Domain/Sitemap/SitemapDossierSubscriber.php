<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class SitemapDossierSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierRepository $dossierRepository,
        private DossierPathHelper $dossierPathHelper,
    ) {
    }

    #[AsEventListener(event: SitemapPopulateEvent::class)]
    public function populate(SitemapPopulateEvent $event): void
    {
        $dossierQuery = $this->dossierRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->setParameter('status', 'published')
            ->getQuery();
        foreach ($dossierQuery->toIterable() as $dossier) {
            $event->getUrlContainer()->addUrl(
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
}
