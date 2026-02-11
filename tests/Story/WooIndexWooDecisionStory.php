<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

use function range;
use function sprintf;
use function Zenstruck\Foundry\faker;

final class WooIndexWooDecisionStory extends Story
{
    private readonly UuidV1 $v1Seed;

    private int $uuidIncrement = 1;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('c0785e96-de2f-11ef-b0e0-315d29be4a5c');
    }

    public function build(): void
    {
        $this->buildUnpublishedDossier();

        $this->buildPublishedDossier(
            number: 1,
            dossierDate: CarbonImmutable::rawParse('2024-04-01 13:37:42'),
            miscDate: CarbonImmutable::rawParse('2024-04-04 01:12:42'),
        );

        $this->buildPublishedDossier(
            number: 2,
            dossierDate: CarbonImmutable::rawParse('2025-02-01 06:37:42'),
            miscDate: CarbonImmutable::rawParse('2025-02-04 10:12:42'),
        );
    }

    public function buildUnpublishedDossier(): void
    {
        $unpublishedWooDecision = WooDecisionFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-woo-decision',
        ]);
        $this->addState('unpublishedWooDecision', $unpublishedWooDecision);
    }

    public function buildPublishedDossier(int $number, CarbonImmutable $dossierDate, CarbonImmutable $miscDate): void
    {
        $wooDecision = CarbonImmutable::withTestNow(
            $dossierDate,
            fn () => WooDecisionFactory::createOne(['dossierNr' => sprintf('my-woo-decision-%s', $number)]),
        );
        $this->addState(sprintf('wooDecision-%s', $number), $wooDecision);

        $documents = [];
        foreach (range(1, 10) as $i) {
            $documents[] = CarbonImmutable::withTestNow(
                $this->getDateForDay($dossierDate, $i),
                fn () => DocumentFactory::new()
                    ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                    ->create([
                        'overwrite_id' => $this->getUniqueUuid(),
                        'documentDate' => CarbonImmutable::now()->subYear()->subMonths(3),
                        'documentNr' => sprintf('%04d-%04d', $number, $i),
                        'dossiers' => [$wooDecision],
                        'judgement' => faker()->randomElement([Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC]),
                    ]),
            );
        }

        // Add some documents that should not be processed by WooIndex
        foreach (range(11, 12) as $i) {
            $documents[] = CarbonImmutable::withTestNow(
                $this->getDateForDay($dossierDate, $i),
                fn () => DocumentFactory::new()
                    ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                    ->create([
                        'overwrite_id' => $this->getUniqueUuid(),
                        'documentDate' => CarbonImmutable::now()->subYear()->subMonths(3),
                        'documentNr' => sprintf('%04d-%04d', $number, $i),
                        'dossiers' => [$wooDecision],
                        'judgement' => faker()->randomElement([Judgement::ALREADY_PUBLIC, Judgement::NOT_PUBLIC]),
                    ]),
            );
        }

        $this->addToPool(sprintf('documents-%s', $number), $documents);

        $wooDecisionAttachments = [];
        foreach (range(13, 15) as $i) {
            $wooDecisionAttachments[] = CarbonImmutable::withTestNow(
                $this->getDateForDay($miscDate, $i),
                fn () => WooDecisionAttachmentFactory::new()
                    ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                    ->create([
                        'overwrite_id' => $this->getUniqueUuid(),
                        'formalDate' => CarbonImmutable::now()->subDays(10)->startOfDay()->toDateTimeImmutable(),
                        'dossier' => $wooDecision,
                    ])
            );
        }
        $this->addToPool(sprintf('attachments-%s', $number), $wooDecisionAttachments);

        $wooDecisionMainDocument = CarbonImmutable::withTestNow(
            $miscDate,
            fn () => WooDecisionMainDocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => CarbonImmutable::now()->subDays(5)->startOfDay()->toDateTimeImmutable(),
                    'dossier' => $wooDecision,
                ]),
        );
        $this->addState(sprintf('mainDocument-%s', $number), $wooDecisionMainDocument);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::create(year: 2024, month: 4, day: $this->uuidIncrement++, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }

    private function getDateForDay(CarbonImmutable $date, int $day): CarbonImmutable
    {
        return CarbonImmutable::createStrict(
            year: $date->year,
            month: $date->month,
            day: $day,
            hour: $date->hour,
            minute: $date->minute,
            second: $date->second,
        );
    }
}
