<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Integration\SharedWebTestCase;

class DocumentPrefixRepositoryTest extends SharedWebTestCase
{
    public function testGetPaginated(): void
    {
        $organisation = OrganisationFactory::createOne();
        $documentPrefixCount = $this->getFaker()->numberBetween(1, 5);
        DocumentPrefixFactory::createMany($documentPrefixCount, [
            'organisation' => $organisation,
        ]);

        $otherOrganisation = OrganisationFactory::createOne();
        DocumentPrefixFactory::createMany($this->getFaker()->numberBetween(1, 5), [
            'organisation' => $otherOrganisation,
        ]);

        $result = self::fromContainer(DocumentPrefixRepository::class)->getByOrganisation($organisation, 100, null);

        self::assertCount($documentPrefixCount, $result);
        self::assertContainsOnlyInstancesOf(DocumentPrefix::class, $result);
    }

    public function testGetAlphabeticallyFirstByOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        DocumentPrefixFactory::createOne([
            'organisation' => $organisation,
            'prefix' => 'BBB',
        ]);
        DocumentPrefixFactory::createOne([
            'organisation' => $organisation,
            'prefix' => 'AAA',
        ]);
        DocumentPrefixFactory::createOne([
            'organisation' => $organisation,
            'prefix' => 'CCC',
        ]);

        $documentPrefixRepository = self::fromContainer(DocumentPrefixRepository::class);
        $result = $documentPrefixRepository->getAlphabeticallyFirstByOrganisation($organisation);

        self::assertEquals('AAA', $result?->getPrefix());
    }
}
