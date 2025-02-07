<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapMainDocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private MainDocumentRepository $mainDocumentRepository,
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
        $mainDocumentQuery = $this->mainDocumentRepository->getAllPublishedQuery();

        /** @var AbstractMainDocument $mainDocument */
        foreach ($mainDocumentQuery->toIterable() as $mainDocument) {
            $dossier = $mainDocument->getDossier();
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $event->getUrlGenerator()->generate(
                        sprintf('app_%s_document_detail', $dossier->getType()->getValueForRouteName()),
                        [
                            'prefix' => $dossier->getDocumentPrefix(),
                            'dossierId' => $dossier->getDossierNr(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                    $mainDocument->getUpdatedAt(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'main_documents',
            );
            $this->doctrine->detach($mainDocument);
        }
    }
}
