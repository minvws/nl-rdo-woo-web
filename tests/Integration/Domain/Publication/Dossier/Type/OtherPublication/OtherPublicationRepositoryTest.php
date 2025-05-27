<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\OtherPublication;

use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use App\Enum\ApplicationMode;
use App\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OtherPublicationRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): OtherPublicationRepository
    {
        /** @var OtherPublicationRepository */
        return self::getContainer()->get(OtherPublicationRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = OtherPublicationFactory::createOne([
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
