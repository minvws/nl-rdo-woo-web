<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Service\RevokedUrlService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

use function iterator_to_array;

class RevokedUrlServiceTest extends UnitTestCase
{
    public function testGetUrls(): void
    {
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentNumberA = $this->getFaker()->documentNumber();
        $documentNumberB = $this->getFaker()->documentNumber();
        $urlA = $this->getFaker()->url();
        $urlB = $this->getFaker()->url();
        $urlC = $this->getFaker()->url();
        $urlD = $this->getFaker()->url();

        $conceptDossier = Mockery::mock(WooDecision::class);
        $conceptDossier->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);

        $publishedDossier = Mockery::mock(WooDecision::class);
        $publishedDossier->expects('getStatus')->times(2)->andReturn(DossierStatus::PUBLISHED);
        $publishedDossier->expects('getDocumentPrefix')->times(2)->andReturn($documentPrefix);
        $publishedDossier->expects('getDossierNumber')->times(2)->andReturn($dossierNumber);

        $documentInConceptDossier = Mockery::mock(Document::class);
        $documentInConceptDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier]));

        $documentInPublishedDossier = Mockery::mock(Document::class);
        $documentInPublishedDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$publishedDossier]));
        $documentInPublishedDossier->expects('getDocumentNumber')->andReturn($documentNumberA);

        $documentInConceptAndPublishedDossier = Mockery::mock(Document::class);
        $documentInConceptAndPublishedDossier->expects('getDossiers')
            ->andReturn(new ArrayCollection([$conceptDossier, $publishedDossier]));
        $documentInConceptAndPublishedDossier->expects('getDocumentNumber')->andReturn($documentNumberB);

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('getRevokedDocumentsInPublicDossiers')->andReturn([
            $documentInConceptDossier,
            $documentInPublishedDossier,
            $documentInConceptAndPublishedDossier,
        ]);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator->expects('buildUrlFromRoute')
            ->with('app_document_detail', [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'documentNumber' => $documentNumberA->toString(),
            ])
            ->andReturn(Url::create($urlA));
        $publicUrlGenerator->expects('buildUrlFromRoute')
            ->with('app_legacy_document_detail', [
                'dossierNumber' => $dossierNumber,
                'documentNumber' => $documentNumberA->toString(),
            ])
            ->andReturn(Url::create($urlB));
        $publicUrlGenerator->expects('buildUrlFromRoute')
            ->with('app_document_detail', [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'documentNumber' => $documentNumberB->toString(),
            ])
            ->andReturn(Url::create($urlC));
        $publicUrlGenerator->expects('buildUrlFromRoute')
            ->with('app_legacy_document_detail', [
                'dossierNumber' => $dossierNumber,
                'documentNumber' => $documentNumberB->toString(),
            ])
            ->andReturn(Url::create($urlD));

        $revokedUrlService = new RevokedUrlService($documentRepository, $publicUrlGenerator);

        self::assertSame(
            [$urlA, $urlB, $urlC, $urlD],
            iterator_to_array($revokedUrlService->getUrls(), false),
        );
    }
}
