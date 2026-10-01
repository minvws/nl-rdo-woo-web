<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Service\DocumentDossierUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class DocumentDossierUrlGeneratorTest extends UnitTestCase
{
    public function testGeneratesDossierDocumentUrl(): void
    {
        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn('PREFIX');
        $dossier->expects('getDossierNumber')->andReturn('dossier-123');

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNumber')
            ->andReturn($documentNumber = DocumentNumber::fromString('PREFIX-matter-123'));

        $publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with('app_document_detail', [
                'documentPrefix' => 'PREFIX',
                'dossierNumber' => 'dossier-123',
                'documentNumber' => $documentNumber->toString(),
            ])
            ->andReturn(Url::create('http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123'));

        $generator = new DocumentDossierUrlGenerator($publicUrlGenerator);

        self::assertSame(
            'http://foo.bar/document/PREFIX/dossier-123/document/PREFIX-matter-123',
            $generator->dossier($document, $dossier),
        );
    }
}
