<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Carbon\CarbonImmutable;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

final class WooIndexAnnualReportStory extends Story
{
    private readonly UuidV1 $v1Seed;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('f32f1564-e573-11ef-b700-2bb719c51bd0');
    }

    public function build(): void
    {
        $annualReport = CarbonImmutable::withTestNow(
            CarbonImmutable::parse('2024-03-10 05:37:42'),
            fn () => AnnualReportFactory::createOne(['dossierNr' => 'my-annual-report-1']),
        );
        $this->addState('annualReport', $annualReport);

        $annualReportMainDocument = CarbonImmutable::withTestNow(
            CarbonImmutable::create('2012-04-04 01:12:42'),
            fn () => AnnualReportMainDocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUuid(1),
                    'formalDate' => CarbonImmutable::now()->subDays(5),
                    'dossier' => $annualReport,
                ]),
        );
        $this->addState('mainDocument', $annualReportMainDocument);

        $annualReportAttachments = [];
        foreach (range(1, 3) as $i) {
            $attachment = CarbonImmutable::withTestNow(
                CarbonImmutable::create(year: 2010, month: 5, day: $i, hour: 13, minute: 37, second: 42),
                function () use ($i, $annualReport) {
                    $document = AnnualReportAttachmentFactory::new()
                        ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                        ->create([
                            'overwrite_id' => $this->getUuid($i),
                            'formalDate' => CarbonImmutable::now()->subDays(10),
                            'dossier' => $annualReport,
                        ]);

                    return $document;
                },
            );

            $annualReportAttachments[] = $attachment;
        }
        $this->addToPool('attachments', $annualReportAttachments);
    }

    private function getUuid(int $day): UuidV6
    {
        $date = CarbonImmutable::create(year: 2024, month: 5, day: $day, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
