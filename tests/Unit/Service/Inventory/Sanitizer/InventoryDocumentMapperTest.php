<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory\Sanitizer;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Service\Inventory\Sanitizer\InventoryDocumentMapper;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryDocumentMapperTest extends UnitTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private string $baseUrl = 'http://foo.bar/';
    private InventoryDocumentMapper $documentMapper;

    public function setUp(): void
    {
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $this->documentMapper = new InventoryDocumentMapper(
            $this->translator,
            $this->urlGenerator,
            $this->baseUrl,
        );

        parent::setUp();
    }

    public function testMap(): void
    {
        $urls = ['http://dummy.url', 'https://x.y.z'];

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('tst-123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('PREFIX');

        $referredDocA = \Mockery::mock(Document::class);
        $referredDocA->shouldReceive('getDocumentNr')->andReturn($refDocIdA = 'PREFIX-matterA-A');
        $referredDocA->shouldReceive('getDocumentId')->andReturn('A');
        $referredDocA->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $referredDocB = \Mockery::mock(Document::class);
        $referredDocB->shouldReceive('getDocumentNr')->andReturn($refDocIdB = 'PREFIX-matterB-B');
        $referredDocB->shouldReceive('getDocumentId')->andReturn('B');
        $referredDocB->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => $refDocIdA])
            ->andReturn('test-url-A');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => $refDocIdB])
            ->andReturn('test-url-B');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDocumentId')->andReturn(123);
        $document->shouldReceive('getDocumentNr')->andReturn($docNr = 'PREFIX-matterA-123');
        $document->shouldReceive('getFileInfo->getName')->andReturn('test-doc-name');
        $document->shouldReceive('getJudgement')->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->shouldReceive('getGrounds')->andReturn(['a', 'b']);
        $document->shouldReceive('getRemark')->andReturnNull();
        $document->shouldReceive('isSuspended')->andReturnTrue();
        $document->shouldReceive('getDossiers->first')->andReturn($dossier);
        $document->shouldReceive('getLinks')->andReturn($urls);
        $document->shouldReceive('getRefersTo')->andReturn(new ArrayCollection([$referredDocA, $referredDocB]));

        $this->translator
            ->expects('trans')
            ->with('public.documents.judgment.short.' . Judgement::PARTIAL_PUBLIC->value)
            ->andReturn('deels openbaar');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => $docNr])
            ->andReturn('test-url');

        $this->assertMatchesSnapshot(
            $this->documentMapper->map($document),
        );
    }
}
