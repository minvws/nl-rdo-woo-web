<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNumber;
use Shared\Tests\Unit\UnitTestCase;

class PrefixedDossierNumberTest extends UnitTestCase
{
    public function testForDossier(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier
            ->expects('getDocumentPrefix')
            ->andReturn('prefix');
        $dossier
            ->expects('getDossierNumber')
            ->andReturn('foo-123');

        self::assertEquals(
            'prefix|foo-123',
            PrefixedDossierNumber::forDossier($dossier),
        );
    }

    public function testWithoutPrefixRemovesPrefix(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNumber::withoutPrefix('prefix|foo-123'),
        );
    }

    public function testWithoutPrefixTrimsWhitespace(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNumber::withoutPrefix('   prefix|foo-123  '),
        );
    }

    public function testWithoutPrefixReturnsValueWithoutPrefix(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNumber::withoutPrefix('foo-123'),
        );
    }

    public function testWithoutPrefixKeepsMultibyteTailIntact(): void
    {
        self::assertEquals(
            'föö-123',
            PrefixedDossierNumber::withoutPrefix('prefix|föö-123'),
        );
    }
}
