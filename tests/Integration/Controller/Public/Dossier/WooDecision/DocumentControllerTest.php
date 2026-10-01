<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\WooDecision;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Service\Search\SearchService;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final class DocumentControllerTest extends SharedWebTestCase
{
    public function testDetailPageIsFoundTroughPublishedWooDecision(): void
    {
        $client = static::createClient();

        $publishedWooDecision = WooDecisionFactory::new()->published()->create();
        $conceptWooDecision = WooDecisionFactory::new()->concept()->create();

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'dossiers' => [$publishedWooDecision, $conceptWooDecision],
        ]);

        $searchService = Mockery::mock(SearchService::class);
        $searchService->expects('isIngested')->andReturn(true);
        self::getContainer()->set(SearchService::class, $searchService);

        $client->request(
            'GET',
            sprintf(
                '/dossier/%s/%s/document/%s',
                $publishedWooDecision->getDocumentPrefix(),
                $publishedWooDecision->getDossierNumber(),
                $document->getDocumentNumber()->toString(),
            ),
        );

        self::assertResponseIsSuccessful();
    }

    public function testDetailPageIsNotFoundThroughConceptWooDecision(): void
    {
        $client = static::createClient();

        $publishedWooDecision = WooDecisionFactory::new()
            ->published()
            ->create();
        $conceptWooDecision = WooDecisionFactory::new()
            ->concept()
            ->create();

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'dossiers' => [$publishedWooDecision, $conceptWooDecision],
        ]);

        $client->request(
            'GET',
            sprintf(
                '/dossier/%s/%s/document/%s',
                $conceptWooDecision->getDocumentPrefix(),
                $conceptWooDecision->getDossierNumber(),
                $document->getDocumentNumber()->toString(),
            ),
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testCanonicalDocumentPageRendersMultiDossierDocument(): void
    {
        $client = static::createClient();
        $searchService = Mockery::mock(SearchService::class);
        $searchService->expects('isIngested')->andReturnTrue();
        self::getContainer()->set(SearchService::class, $searchService);

        $firstDossier = WooDecisionFactory::new()->published()->create();
        $secondDossier = WooDecisionFactory::new()->published()->create();
        DocumentFactory::createOne([
            'dossiers' => [$firstDossier, $secondDossier],
            'documentNumber' => 'PREF-MAT-100',
            'fileInfo' => FileInfoFactory::new(['name' => 'shared.pdf']),
        ]);

        $client->request('GET', '/document/PREF-MAT-100');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('shared.pdf', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('PREF-MAT-100', (string) $client->getResponse()->getContent());
    }

    public function testDossierDocumentPageRendersMultiDossierDocument(): void
    {
        $client = static::createClient();
        $searchService = Mockery::mock(SearchService::class);
        $searchService->expects('isIngested')->andReturnTrue();
        self::getContainer()->set(SearchService::class, $searchService);

        $firstDossier = WooDecisionFactory::new()->published()->create();
        $secondDossier = WooDecisionFactory::new()->published()->create();
        $document = DocumentFactory::createOne([
            'dossiers' => [$firstDossier, $secondDossier],
            'documentNumber' => 'PREF-MAT-101',
            'fileInfo' => FileInfoFactory::new(['name' => 'shared.pdf']),
        ]);

        $client->request(
            'GET',
            sprintf(
                '/dossier/%s/%s/document/%s',
                $firstDossier->getDocumentPrefix(),
                $firstDossier->getDossierNumber(),
                $document->getDocumentNumber()->toString(),
            ),
        );

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('shared.pdf', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('PREF-MAT-101', (string) $client->getResponse()->getContent());
    }

    public function testCanonicalDocumentPageReturnsNotFoundForUnpublishedDocument(): void
    {
        $client = static::createClient();
        $dossier = WooDecisionFactory::new()->concept()->create();
        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNumber' => 'PREF-MAT-102',
        ]);

        $client->request('GET', '/document/PREF-MAT-102');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCanonicalDocumentPageReturnsNotFoundForUnknownDocument(): void
    {
        $client = static::createClient();

        $client->request('GET', '/document/UNKNOWN-DOCUMENT');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDossierDocumentPagePointsToCanonicalDocumentUrl(): void
    {
        $client = static::createClient();
        $searchService = Mockery::mock(SearchService::class);
        $searchService->expects('isIngested')->andReturnTrue();
        self::getContainer()->set(SearchService::class, $searchService);

        $dossier = WooDecisionFactory::new()->published()->create();
        $document = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNumber' => 'PREF-MAT-200',
            'fileInfo' => FileInfoFactory::new(['name' => 'document.pdf']),
        ]);

        $dossierUrl = sprintf(
            '/dossier/%s/%s',
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNumber(),
        );
        $client->request(
            'GET',
            sprintf(
                '%s/document/%s',
                $dossierUrl,
                $document->getDocumentNumber()->toString(),
            ),
        );

        self::assertResponseIsSuccessful();
        self::assertCount(1, $client->getCrawler()->filter('link[rel="canonical"]'));
        self::assertSame(
            sprintf(
                '%s/document/PREF-MAT-200',
                self::getContainer()->getParameter('public_base_url'),
            ),
            $client->getCrawler()->filter('link[rel="canonical"]')->attr('href'),
        );

        $breadcrumbs = $client->getCrawler()->filter('[data-e2e-name="breadcrumb"]');
        self::assertCount(2, $breadcrumbs);
        self::assertSame($dossierUrl, $breadcrumbs->eq(1)->attr('href'));
    }
}
