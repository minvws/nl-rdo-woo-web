<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\AnnualReport;

use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class AnnualReportRepositoryTest extends SharedWebTestCase
{
    private function getRepository(): AnnualReportRepository
    {
        /** @var AnnualReportRepository */
        return self::getContainer()->get(AnnualReportRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = AnnualReportFactory::createOne([
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
