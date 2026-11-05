<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Shared\Controller\Public\Dossier\WooDecision\DocumentController;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

final class DocumentUrlGeneratorTest extends UnitTestCase
{
    public function testCanonicalUrl(): void
    {
        $documentNumber = $this->getFaker()->documentNumber();
        $url = Url::create($this->getFaker()->url());

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with(DocumentController::ROUTE_NAME_DOCUMENT_CANONICAL, [
                'documentNumber' => $documentNumber->toString(),
            ])
            ->andReturn($url);

        self::assertSame($url, new DocumentUrlGenerator($publicUrlGenerator)->canonicalUrl($documentNumber));
    }

    public function testDetailUrl(): void
    {
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentNumber = $this->getFaker()->documentNumber();
        $url = Url::create($this->getFaker()->url());

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn($documentPrefix);
        $dossier->expects('getDossierNumber')->andReturn($dossierNumber);

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')->andReturn($documentNumber);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with(DocumentController::ROUTE_NAME_DOCUMENT_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'documentNumber' => $documentNumber->toString(),
            ])
            ->andReturn($url);

        self::assertSame($url, new DocumentUrlGenerator($publicUrlGenerator)->detailUrl($document, $dossier));
    }
}
