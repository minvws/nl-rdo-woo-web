<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\RevokedUrlService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\RouterInterface;

use function iterator_to_array;

class RevokedUrlServiceTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private RouterInterface&MockInterface $router;
    private string $publicUrl = 'http://test/';
    private RevokedUrlService $service;

    protected function setUp(): void
    {
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->router = Mockery::mock(RouterInterface::class);

        $this->service = new RevokedUrlService(
            $this->documentRepository,
            $this->router,
            $this->publicUrl,
        );

        parent::setUp();
    }

    public function testGetUrls(): void
    {
        $conceptDossier = Mockery::mock(WooDecision::class);
        $conceptDossier->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $publishedDossier = Mockery::mock(WooDecision::class);
        $publishedDossier->expects('getStatus')->times(2)->andReturn(DossierStatus::PUBLISHED);
        $publishedDossier->expects('getDocumentPrefix')->times(2)->andReturn($docPrefix = 'FOO');
        $publishedDossier->expects('getDossierNr')->times(2)->andReturn($dossierNr = '123');

        $documentInConceptDossier = Mockery::mock(Document::class);
        $documentInConceptDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier]));

        $documentInPublishedDossier = Mockery::mock(Document::class);
        $documentInPublishedDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$publishedDossier]));
        $documentInPublishedDossier->expects('getDocumentNr')->andReturn($docNrA = 'D1');

        $documentInConceptAndPublishedDossier = Mockery::mock(Document::class);
        $documentInConceptAndPublishedDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier, $publishedDossier]));
        $documentInConceptAndPublishedDossier->expects('getDocumentNr')->andReturn($docNrB = 'D2');

        $this->router->expects('generate')->with(
            'app_document_detail',
            [
                'prefix' => $docPrefix,
                'dossierId' => $dossierNr,
                'documentId' => $docNrA,
            ],
        )->andReturn('link_A');

        $this->router->expects('generate')->with(
            'app_legacy_document_detail',
            [
                'dossierId' => $dossierNr,
                'documentId' => $docNrA,
            ],
        )->andReturn('link_B');

        $this->router->expects('generate')->with(
            'app_document_detail',
            [
                'prefix' => $docPrefix,
                'dossierId' => $dossierNr,
                'documentId' => $docNrB,
            ],
        )->andReturn('link_C');

        $this->router->expects('generate')->with(
            'app_legacy_document_detail',
            [
                'dossierId' => $dossierNr,
                'documentId' => $docNrB,
            ],
        )->andReturn('link_D');

        $this->documentRepository->expects('getRevokedDocumentsInPublicDossiers')->andReturn([
            $documentInConceptDossier,
            $documentInPublishedDossier,
            $documentInConceptAndPublishedDossier,
        ]);

        $this->assertMatchesJsonSnapshot(iterator_to_array($this->service->getUrls(), false));
    }
}
