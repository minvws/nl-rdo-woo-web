<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\DocumentPrefixRepository;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocumentPrefixRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private DocumentPrefixRepository $documentPrefixRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentPrefixRepository = self::getContainer()->get(DocumentPrefixRepository::class);
    }

    public function testGetPaginated(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $documentPrefixCount = $this->getFaker()->numberBetween(1, 5);
        DocumentPrefixFactory::createMany($documentPrefixCount, [
            'organisation' => $organisation,
        ]);

        $otherOrganisation = OrganisationFactory::createOne()->_real();
        DocumentPrefixFactory::createMany($this->getFaker()->numberBetween(1, 5), [
            'organisation' => $otherOrganisation,
        ]);

        $result = $this->documentPrefixRepository->getByOrganisation($organisation, 100, null);

        self::assertCount($documentPrefixCount, $result);
        self::assertContainsOnlyInstancesOf(DocumentPrefix::class, $result);
    }
}
