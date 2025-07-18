<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use App\Service\Security\ApplicationMode\ApplicationMode;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DispositionRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): DispositionRepository
    {
        /** @var DispositionRepository */
        return self::getContainer()->get(DispositionRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = DispositionFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
