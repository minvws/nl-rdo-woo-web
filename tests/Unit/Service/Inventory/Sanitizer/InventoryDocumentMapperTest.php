<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory\Sanitizer;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\Sanitizer\InventoryDocumentMapper;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryDocumentMapperTest extends UnitTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private string $baseUrl = 'http://foo.bar/';
    private InventoryDocumentMapper $documentMapper;

    protected function setUp(): void
    {
        $this->translator = Mockery::mock(TranslatorInterface::class);
        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);

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

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDossierNr')->times(3)->andReturn('tst-123');
        $dossier->expects('getDocumentPrefix')->times(9)->andReturn('PREFIX');
        $dossier->expects('getTitle')->andReturn('Foo Bar');

        $referredDocA = Mockery::mock(Document::class);
        $referredDocA->expects('getDocumentNr')->times(2)->andReturn($refDocIdA = 'PREFIX-matterA-A');
        $referredDocA->expects('getDocumentId')->times(3)->andReturn('A');
        $referredDocA->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([$dossier]));

        $referredDocB = Mockery::mock(Document::class);
        $referredDocB->expects('getDocumentNr')->times(2)->andReturn($refDocIdB = 'PREFIX-matterB-B');
        $referredDocB->expects('getDocumentId')->times(3)->andReturn('B');
        $referredDocB->expects('getDossiers')->times(2)->andReturn(new ArrayCollection([$dossier]));

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => $refDocIdA])
            ->andReturn('test-url-A');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => $refDocIdB])
            ->andReturn('test-url-B');

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')->times(4)->andReturn(123);
        $document->expects('getDocumentNr')->times(3)->andReturn($docNr = 'PREFIX-matterA-123');
        $document->expects('getFileInfo->getName')->andReturn('test-doc-name');
        $document->expects('getJudgement')->times(2)->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->expects('getGrounds')->andReturn(['a', 'b']);
        $document->expects('getRemark')->andReturnNull();
        $document->expects('isSuspended')->andReturnTrue();
        $document->expects('getDossiers->first')->times(2)->andReturn($dossier);
        $document->expects('getLinks')->andReturn($urls);
        $document->expects('getRefersTo')->times(2)->andReturn(new ArrayCollection([$referredDocA, $referredDocB]));

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
