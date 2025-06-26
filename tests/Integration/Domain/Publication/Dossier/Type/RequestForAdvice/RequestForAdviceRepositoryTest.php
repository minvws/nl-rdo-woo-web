<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\RequestForAdvice;

use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use App\Enum\ApplicationMode;
use App\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RequestForAdviceRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): RequestForAdviceRepository
    {
        /** @var RequestForAdviceRepository */
        return self::getContainer()->get(RequestForAdviceRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = RequestForAdviceFactory::createOne();

        $result = $this->getRepository()->getSearchResultViewModel(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
