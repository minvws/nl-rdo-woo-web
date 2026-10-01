<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

use function array_key_exists;

#[AsEventListener]
readonly class SitemapDocumentSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DossierRepository $dossierRepository,
        private DocumentCanonicalUrlGenerator $documentCanonicalUrlGenerator,
    ) {
    }

    public function __invoke(SitemapPopulateEvent $event): void
    {
        $dossierQuery = $this->dossierRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.status = :status')
            ->andWhere('d INSTANCE OF :type')
            ->setParameter('status', 'published')
            ->setParameter('type', DossierType::WOO_DECISION)
            ->getQuery();

        /** @var array<string, true> $processedDocumentNumbers */
        $processedDocumentNumbers = [];

        foreach ($dossierQuery->toIterable() as $dossier) {
            Assert::isInstanceOf($dossier, WooDecision::class);

            foreach ($dossier->getDocuments() as $document) {
                $documentNumber = $document->getDocumentNumber();
                $documentNumberString = $documentNumber->toString();

                if (array_key_exists($documentNumberString, $processedDocumentNumbers)) {
                    $this->doctrine->detach($document);

                    continue;
                }

                $processedDocumentNumbers[$documentNumberString] = true;
                $canonicalDocumentUrl = $this->documentCanonicalUrlGenerator->canonical($documentNumber);

                $event->getUrlContainer()->addUrl(
                    new UrlConcrete(
                        $canonicalDocumentUrl,
                        $document->getUpdatedAt(),
                        UrlConcrete::CHANGEFREQ_MONTHLY,
                        0.8,
                    ),
                    'documents',
                );
                $this->doctrine->detach($document);
            }

            $this->doctrine->detach($dossier);
        }
    }
}
