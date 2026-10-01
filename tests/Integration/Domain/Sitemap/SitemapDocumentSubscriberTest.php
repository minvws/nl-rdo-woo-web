<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Sitemap\SitemapDocumentSubscriber;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

final class SitemapDocumentSubscriberTest extends SharedWebTestCase
{
    public function testDocumentIsListedOnceWhenLinkedToMultiplePublishedDossiers(): void
    {
        $firstPublishedDossier = WooDecisionFactory::new()->published()->create();
        $secondPublishedDossier = WooDecisionFactory::new()->published()->create();
        $document = DocumentFactory::createOne([
            'dossiers' => [$firstPublishedDossier, $secondPublishedDossier],
        ]);
        $documentNumber = $document->getDocumentNumber();
        self::fromContainer(EntityManagerInterface::class)->clear();

        $urlContainer = new class implements UrlContainerInterface {
            /** @var list<string> */
            public array $documentUrls = [];

            public function addUrl(Url $url, string $section): void
            {
                if ($section === 'documents' && $url instanceof UrlConcrete) {
                    $this->documentUrls[] = $url->getLoc();
                }
            }
        };

        $event = new SitemapPopulateEvent(
            $urlContainer,
            self::fromContainer(UrlGeneratorInterface::class),
        );
        self::fromContainer(SitemapDocumentSubscriber::class)($event);

        self::assertSame(
            [
                sprintf(
                    '%s/document/%s',
                    self::getContainer()->getParameter('public_base_url'),
                    $documentNumber->toString(),
                ),
            ],
            $urlContainer->documentUrls,
        );
    }
}
