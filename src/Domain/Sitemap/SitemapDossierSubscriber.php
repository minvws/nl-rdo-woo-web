<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SitemapDossierSubscriber implements EventSubscriberInterface
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
        $dossierQuery = $this->dossierRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->setParameter('status', 'published')
            ->getQuery()
        ;
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
