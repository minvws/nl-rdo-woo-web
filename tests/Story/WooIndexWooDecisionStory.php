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
use Shared\ValueObject\PlainDate;
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
            dossierDate: CarbonImmutable::parse('2024-04-01 13:37:42'),
            miscDate: CarbonImmutable::parse('2024-04-04 01:12:42'),
        );

        $this->buildPublishedDossier(
            number: 2,
            dossierDate: CarbonImmutable::parse('2025-02-01 06:37:42'),
            miscDate: CarbonImmutable::parse('2025-02-04 10:12:42'),
        );
    }

    public function buildUnpublishedDossier(): void
    {
        $unpublishedWooDecision = WooDecisionFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-woo-decision',
            'createdAt' => CarbonImmutable::parse('2025-01-01'),
            'updatedAt' => CarbonImmutable::parse('2025-01-01'),
        ]);
        $this->addState('unpublishedWooDecision', $unpublishedWooDecision);
    }

    public function buildPublishedDossier(int $number, CarbonImmutable $dossierDate, CarbonImmutable $miscDate): void
    {
        $wooDecision = WooDecisionFactory::createOne([
            'dossierNr' => sprintf('my-woo-decision-%s', $number),
            'createdAt' => $dossierDate,
            'updatedAt' => $dossierDate,
        ]);
        $this->addState(sprintf('wooDecision-%s', $number), $wooDecision);

        $documents = [];
        foreach (range(1, 10) as $i) {
            $documents[] = DocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'documentDate' => PlainDate::createFromFormat('Y-m-d', $dossierDate->setDay($i)->subYear()->subMonths(3)->format('Y-m-d')),
                    'documentNr' => sprintf('%04d-%04d', $number, $i),
                    'dossiers' => [$wooDecision],
                    'judgement' => faker()->randomElement([Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC]),
                    'createdAt' => $dossierDate->setDay($i),
                    'updatedAt' => $dossierDate->setDay($i),
                ]);
        }

        // Add some documents that should not be processed by WooIndex
        foreach (range(11, 12) as $i) {
            $documents[] = DocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'documentDate' => PlainDate::createFromFormat('Y-m-d', $dossierDate->setDay($i)->subYear()->subMonths(3)->format('Y-m-d')),
                    'documentNr' => sprintf('%04d-%04d', $number, $i),
                    'dossiers' => [$wooDecision],
                    'judgement' => faker()->randomElement([Judgement::ALREADY_PUBLIC, Judgement::NOT_PUBLIC]),
                    'createdAt' => $dossierDate->setDay($i),
                    'updatedAt' => $dossierDate->setDay($i),
                ]);
        }

        $this->addToPool(sprintf('documents-%s', $number), $documents);

        $wooDecisionAttachments = [];
        foreach (range(13, 15) as $i) {
            $wooDecisionAttachments[] = WooDecisionAttachmentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => PlainDate::create($miscDate->setDay($i)->subDays(10)->startOfDay()->toDateTimeImmutable()->format('Y-m-d')),
                    'dossier' => $wooDecision,
                    'createdAt' => $miscDate->setDay($i),
                    'updatedAt' => $miscDate->setDay($i),
                ]);
        }
        $this->addToPool(sprintf('attachments-%s', $number), $wooDecisionAttachments);

        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
            ->create([
                'overwrite_id' => $this->getUniqueUuid(),
                'formalDate' => PlainDate::create($miscDate->subDays(5)->startOfDay()->toDateTimeImmutable()->format('Y-m-d')),
                'dossier' => $wooDecision,
                'createdAt' => $miscDate,
                'updatedAt' => $miscDate,
            ]);
        $this->addState(sprintf('mainDocument-%s', $number), $wooDecisionMainDocument);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::parse('2024-03-31 13:37:42')->addDays($this->uuidIncrement++);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
