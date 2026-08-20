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
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DossierTitle;
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
        $dossier->expects('getDossierNumber')->times(3)->andReturn('tst-123');
        $dossier->expects('getDocumentPrefix')->times(3)->andReturn('PREFIX');
        $dossier->expects('getTitle')->andReturn(DossierTitle::create('Foo Bar'));

        $referredDocA = Mockery::mock(Document::class);
        $referredDocA->expects('getDocumentNumber')->times(2)->andReturn($refDocIdA = 'PREFIX-matterA-a');
        $referredDocA->expects('getDocumentId')->never();
        $referredDocA->expects('getDossiers')->times(1)->andReturn(new ArrayCollection([$dossier]));

        $referredDocB = Mockery::mock(Document::class);
        $referredDocB->expects('getDocumentNumber')->times(2)->andReturn($refDocIdB = 'PREFIX-matterB-b');
        $referredDocB->expects('getDocumentId')->never();
        $referredDocB->expects('getDossiers')->times(1)->andReturn(new ArrayCollection([$dossier]));

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['documentPrefix' => 'PREFIX', 'dossierNumber' => 'tst-123', 'documentNumber' => $refDocIdA])
            ->andReturn('test-url-A');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['documentPrefix' => 'PREFIX', 'dossierNumber' => 'tst-123', 'documentNumber' => $refDocIdB])
            ->andReturn('test-url-B');

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')->times(1)->andReturn(DocumentId::create('123'));
        $document->expects('getDocumentNumber')->times(2)->andReturn($docNr = 'PREFIX-matterA-123');
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
            ->with('app_document_detail', ['documentPrefix' => 'PREFIX', 'dossierNumber' => 'tst-123', 'documentNumber' => $docNr])
            ->andReturn('test-url');

        $this->assertMatchesSnapshot(
            $this->documentMapper->map($document),
        );
    }
}
