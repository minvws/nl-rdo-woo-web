<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\ValueObject\PlainDate;
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
        $annualReport = AnnualReportFactory::createOne([
            'dossierNr' => 'my-annual-report-1',
            'createdAt' => CarbonImmutable::parse('2022-02-01 13:37:42'),
            'updatedAt' => CarbonImmutable::parse('2022-02-01 13:37:42'),
        ]);
        $this->addState('annualReport', $annualReport);

        $unpublishedAnnualReport = AnnualReportFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-annual-report-2',
        ]);
        $this->addState('unpublishedAnnualReport', $unpublishedAnnualReport);

        $annualReportMainDocument = AnnualReportMainDocumentFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
            ->create([
                'overwrite_id' => $this->getUniqueUuid(),
                'formalDate' => PlainDate::create('2022-01-30'),
                'dossier' => $annualReport,
                'createdAt' => CarbonImmutable::parse('2022-02-04 01:12:42'),
                'updatedAt' => CarbonImmutable::parse('2022-02-04 01:12:42'),
            ]);
        $this->addState('mainDocument', $annualReportMainDocument);

        $annualReportAttachments = [];
        foreach (range(1, 3) as $i) {
            $annualReportAttachments[] = AnnualReportAttachmentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => PlainDate::create('2022-01-21')->addDays($i),
                    'dossier' => $annualReport,
                    'createdAt' => CarbonImmutable::parse('2022-01-31 13:37:42')->addDays($i),
                    'updatedAt' => CarbonImmutable::parse('2022-01-31 13:37:42')->addDays($i),
                ]);
        }
        $this->addToPool('attachments', $annualReportAttachments);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::parse('2022-01-31 13:37:42')->addDays($this->uuidIncrement++);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
