<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapMainDocumentSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private MainDocumentRepository $mainDocumentRepository,
    ) {
    }

    #[AsEventListener(event: SitemapPopulateEvent::class)]
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
