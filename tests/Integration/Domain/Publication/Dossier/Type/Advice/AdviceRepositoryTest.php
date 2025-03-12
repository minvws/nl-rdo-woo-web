<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use App\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AdviceRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AdviceRepository
    {
        /** @var AdviceRepository */
        return self::getContainer()->get(AdviceRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = AdviceFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel($dossier->getDocumentPrefix(), $dossier->getDossierNr());
        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
