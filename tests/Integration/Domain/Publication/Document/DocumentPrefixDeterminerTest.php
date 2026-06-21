<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Document;

use InvalidArgumentException;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Integration\SharedWebTestCase;

use function strtoupper;

class DocumentPrefixDeterminerTest extends SharedWebTestCase
{
    public function testForOrganisationWithoutPrefixes(): void
    {
        $organisation = OrganisationFactory::createOne();

        $documentPrefixDeterminer = new DocumentPrefixDeterminer(self::fromContainer(DocumentPrefixRepository::class));

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessageIs('no document prefix found for organisation');
        $documentPrefixDeterminer->forOrganisation($organisation);
    }

    public function testForOrganisationWithSinglePrefix(): void
    {
        $prefix = self::getFaker()->word();

        $organisation = OrganisationFactory::createOne();
        DocumentPrefixFactory::createOne([
            'prefix' => $prefix,
            'organisation' => $organisation,
        ]);

        $documentPrefixDeterminer = new DocumentPrefixDeterminer(self::fromContainer(DocumentPrefixRepository::class));

        $result = $documentPrefixDeterminer->forOrganisation($organisation);
        self::assertEquals(strtoupper($prefix), $result);
    }

    public function testForOrganisationWithMultiplePrefixes(): void
    {
        $organisation = OrganisationFactory::createOne();
        DocumentPrefixFactory::createOne([
            'prefix' => 'A',
            'organisation' => $organisation,
        ]);
        DocumentPrefixFactory::createOne([
            'prefix' => 'B',
            'organisation' => $organisation,
        ]);
        DocumentPrefixFactory::createOne([
            'prefix' => 'C',
            'organisation' => $organisation,
        ]);

        $documentPrefixDeterminer = new DocumentPrefixDeterminer(self::fromContainer(DocumentPrefixRepository::class));

        $result = $documentPrefixDeterminer->forOrganisation($organisation);
        self::assertEquals('A', $result);
    }
}
