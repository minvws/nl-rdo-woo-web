<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

use function range;

final class WooIndexAnnualReportStory extends Story
{
    private readonly UuidV1 $v1Seed;

    private int $uuidIncrement = 1;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('f32f1564-e573-11ef-b700-2bb719c51bd0');
    }

    public function build(): void
    {
        $annualReport = CarbonImmutable::withTestNow(
            CarbonImmutable::parse('2022-02-01 13:37:42'),
            fn () => AnnualReportFactory::createOne(['dossierNr' => 'my-annual-report-1']),
        );
        $this->addState('annualReport', $annualReport);

        $unpublishedAnnualReport = AnnualReportFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-annual-report-2',
        ]);
        $this->addState('unpublishedAnnualReport', $unpublishedAnnualReport);

        $annualReportMainDocument = CarbonImmutable::withTestNow(
            CarbonImmutable::create('2022-02-04 01:12:42'),
            fn () => AnnualReportMainDocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => CarbonImmutable::now()->subDays(5)->startOfDay()->toDateTimeImmutable(),
                    'dossier' => $annualReport,
                ]),
        );
        $this->addState('mainDocument', $annualReportMainDocument);

        $annualReportAttachments = [];
        foreach (range(1, 3) as $i) {
            $annualReportAttachments[] = CarbonImmutable::withTestNow(
                CarbonImmutable::create(year: 2022, month: 2, day: $i, hour: 13, minute: 37, second: 42),
                fn () => AnnualReportAttachmentFactory::new()
                    ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                    ->create([
                        'overwrite_id' => $this->getUniqueUuid(),
                        'formalDate' => CarbonImmutable::now()->subDays(10)->startOfDay()->toDateTimeImmutable(),
                        'dossier' => $annualReport,
                    ])
            );
        }
        $this->addToPool('attachments', $annualReportAttachments);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::create(year: 2022, month: 2, day: $this->uuidIncrement++, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
