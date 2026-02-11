<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use Shared\Tests\Unit\UnitTestCase;

class PrefixedDossierNrTest extends UnitTestCase
{
    public function testForDossier(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier
            ->shouldReceive('getDocumentPrefix')
            ->andReturn('prefix');
        $dossier
            ->shouldReceive('getDossierNr')
            ->andReturn('foo-123');

        self::assertEquals(
            'prefix|foo-123',
            PrefixedDossierNr::forDossier($dossier),
        );
    }

    public function testWithoutPrefixRemovesPrefix(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNr::withoutPrefix('prefix|foo-123'),
        );
    }

    public function testWithoutPrefixTrimsWhitespace(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNr::withoutPrefix('   prefix|foo-123  '),
        );
    }

    public function testWithoutPrefixReturnsValueWithoutPrefix(): void
    {
        self::assertEquals(
            'foo-123',
            PrefixedDossierNr::withoutPrefix('foo-123'),
        );
    }
}
