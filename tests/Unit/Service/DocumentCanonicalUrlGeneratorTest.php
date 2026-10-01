<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Mockery;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

final class DocumentCanonicalUrlGeneratorTest extends UnitTestCase
{
    public function testCanonicalReturnsTheGeneratedCanonicalUrl(): void
    {
        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->with(
                'app_document_canonical',
                ['documentNumber' => 'PREF-MAT-123'],
            )
            ->andReturn(Url::create($expectedUrl = 'https://example.test/document/PREF-MAT-123'));

        $generator = new DocumentCanonicalUrlGenerator($publicUrlGenerator);

        self::assertSame(
            $expectedUrl,
            $generator->canonical(DocumentNumber::fromString('PREF-MAT-123')),
        );
    }
}
