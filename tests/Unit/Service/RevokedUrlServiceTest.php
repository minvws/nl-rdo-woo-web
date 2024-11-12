<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Service\RevokedUrlService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Routing\RouterInterface;

class RevokedUrlServiceTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private RouterInterface&MockInterface $router;
    private string $publicUrl = 'http://test/';
    private RevokedUrlService $service;

    public function setUp(): void
    {
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->router = \Mockery::mock(RouterInterface::class);

        $this->service = new RevokedUrlService(
            $this->documentRepository,
            $this->router,
            $this->publicUrl,
        );

        parent::setUp();
    }

    public function testGetUrls(): void
    {
        $conceptDossier = \Mockery::mock(WooDecision::class);
        $conceptDossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $publishedDossier = \Mockery::mock(WooDecision::class);
        $publishedDossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $publishedDossier->shouldReceive('getDocumentPrefix')->andReturn($docPrefix = 'FOO');
        $publishedDossier->shouldReceive('getDossierNr')->andReturn($dossierNr = '123');

        $documentInConceptDossier = \Mockery::mock(Document::class);
        $documentInConceptDossier->shouldReceive('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier]));

        $documentInPublishedDossier = \Mockery::mock(Document::class);
        $documentInPublishedDossier->shouldReceive('getDossiers')
            ->andReturn(new ArrayCollection([$publishedDossier]));
        $documentInPublishedDossier->shouldReceive('getDocumentNr')->andReturn($docNrA = 'D1');

        $documentInConceptAndPublishedDossier = \Mockery::mock(Document::class);
        $documentInConceptAndPublishedDossier->shouldReceive('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier, $publishedDossier]));
        $documentInConceptAndPublishedDossier->shouldReceive('getDocumentNr')->andReturn($docNrB = 'D2');

        $this->router->shouldReceive('generate')->with(
            'app_document_detail',
            [
                'prefix' => $docPrefix,
                'dossierId' => $dossierNr,
                'documentId' => $docNrA,
            ]
        )->andReturn('link_A');

        $this->router->shouldReceive('generate')->with(
            'app_legacy_document_detail',
            [
                'dossierId' => $dossierNr,
                'documentId' => $docNrA,
            ]
        )->andReturn('link_B');

        $this->router->shouldReceive('generate')->with(
            'app_document_detail',
            [
                'prefix' => $docPrefix,
                'dossierId' => $dossierNr,
                'documentId' => $docNrB,
            ]
        )->andReturn('link_C');

        $this->router->shouldReceive('generate')->with(
            'app_legacy_document_detail',
            [
                'dossierId' => $dossierNr,
                'documentId' => $docNrB,
            ]
        )->andReturn('link_D');

        $this->documentRepository->expects('getRevokedDocumentsInPublicDossiers')->andReturn([
            $documentInConceptDossier,
            $documentInPublishedDossier,
            $documentInConceptAndPublishedDossier,
        ]);

        $this->assertMatchesJsonSnapshot(
            iterator_to_array($this->service->getUrls())
        );
    }
}
