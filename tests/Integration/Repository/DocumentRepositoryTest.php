<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\DocumentRepository;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private DocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(DocumentRepository::class);
    }

    public function testFindBySearchTerm(): void
    {
        $organisation = OrganisationFactory::createOne();

        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-1234',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);

        $result = $this->repository->findBySearchTerm(
            $documentNr,
            10,
            $organisation->_real(),
        );

        self::assertCount(2, $result);
    }

    public function testFindBySearchTermFilteredByUuid(): void
    {
        $organisation = OrganisationFactory::createOne();
        $dossierOne = WooDecisionFactory::createOne(['organisation' => $organisation]);

        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [$dossierOne],
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-1235',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);

        $result = $this->repository->findBySearchTerm(
            $documentNr,
            10,
            $organisation->_real(),
            dossierId: $dossierOne->_real()->getId(),
        );

        self::assertCount(1, $result);
    }
}
