<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\AnnualReport;

use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;

final class AnnualReportRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = AnnualReportFactory::createOne([
            'dateFrom' => PlainDate::today(),
        ]);

        $result = self::fromContainer(AnnualReportRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNr(),
                ApplicationMode::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
