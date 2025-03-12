<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PrefixedDossierNrTest extends MockeryTestCase
{
    public function testForDossier(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
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
