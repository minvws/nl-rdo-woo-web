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
use Shared\Service\Inventory\Sanitizer\InventoryDocumentUrlStrategyInterface;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\DossierTitle;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryDocumentMapperTest extends UnitTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private InventoryDocumentUrlStrategyInterface&MockInterface $urlStrategy;
    private InventoryDocumentMapper $documentMapper;

    protected function setUp(): void
    {
        $this->translator = Mockery::mock(TranslatorInterface::class);
        $this->urlStrategy = Mockery::mock(InventoryDocumentUrlStrategyInterface::class);

        $this->documentMapper = new InventoryDocumentMapper(
            $this->translator,
        );

        parent::setUp();
    }

    public function testMap(): void
    {
        $urls = ['http://dummy.url', 'https://x.y.z'];

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getTitle')->andReturn(DossierTitle::create('Foo Bar'));

        $referredDocA = Mockery::mock(Document::class);
        $referredDocA->expects('getDocumentNumber')
            ->once()
            ->andReturn(DocumentNumber::fromString('PREFIX-matterA-a'));
        $referredDocA->expects('getDocumentId')->never();

        $referredDocB = Mockery::mock(Document::class);
        $referredDocB->expects('getDocumentNumber')
            ->once()
            ->andReturn(DocumentNumber::fromString('PREFIX-matterB-b'));
        $referredDocB->expects('getDocumentId')->never();

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')->andReturn(DocumentId::create('123'));
        $document->expects('getDocumentNumber')
            ->once()
            ->andReturn(DocumentNumber::fromString('PREFIX-matterA-123'));
        $document->expects('getFileInfo->getName')->andReturn('test-doc-name');
        $document->expects('getJudgement')->times(2)->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->expects('getGrounds')->andReturn(['a', 'b']);
        $document->expects('getRemark')->andReturnNull();
        $document->expects('isSuspended')->andReturnTrue();
        $document->expects('getLinks')->andReturn($urls);
        $document->expects('getRefersTo')->times(2)->andReturn(new ArrayCollection([$referredDocA, $referredDocB]));

        $this->translator
            ->expects('trans')
            ->with('public.documents.judgment.short.' . Judgement::PARTIAL_PUBLIC->value)
            ->andReturn('deels openbaar');

        $this->urlStrategy
            ->expects('generateDocumentUrl')
            ->with($document, $dossier)
            ->andReturn('http://foo.bar/test-url');
        $this->urlStrategy
            ->expects('generateRelatedDocumentUrl')
            ->with($referredDocA, $dossier)
            ->andReturn('http://foo.bar/test-url-A');
        $this->urlStrategy
            ->expects('generateRelatedDocumentUrl')
            ->with($referredDocB, $dossier)
            ->andReturn('http://foo.bar/test-url-B');

        $this->assertMatchesSnapshot(
            $this->documentMapper->map($document, $dossier, $this->urlStrategy),
        );
    }
}
