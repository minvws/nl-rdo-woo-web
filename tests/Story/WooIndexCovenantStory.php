<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Carbon\CarbonImmutable;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

final class WooIndexCovenantStory extends Story
{
    private readonly UuidV1 $v1Seed;

    private int $_uuid_increment = 1;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('06626570-e581-11ef-82b2-09e15b3f6ce0');
    }

    public function build(): void
    {
        $covenant = CarbonImmutable::withTestNow(
            CarbonImmutable::parse('2023-03-01 13:37:42'),
            fn () => CovenantFactory::createOne(['dossierNr' => 'my-covenant-1']),
        );
        $this->addState('covenant', $covenant);

        $unpublishedCovenant = CovenantFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-covenant-2',
        ]);
        $this->addState('unpublishedCovenant', $unpublishedCovenant);

        $covenantMainDocument = CarbonImmutable::withTestNow(
            CarbonImmutable::create('2023-03-04 01:12:42'),
            fn () => CovenantMainDocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => CarbonImmutable::now()->subDays(5)->startOfDay()->toDateTimeImmutable(),
                    'dossier' => $covenant,
                ]),
        );
        $this->addState('mainDocument', $covenantMainDocument);

        $covenantAttachments = [];
        foreach (range(1, 3) as $i) {
            $covenantAttachments[] = CarbonImmutable::withTestNow(
                CarbonImmutable::create(year: 2023, month: 3, day: $i, hour: 13, minute: 37, second: 42),
                fn () => CovenantAttachmentFactory::new()
                    ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                    ->create([
                        'overwrite_id' => $this->getUniqueUuid(),
                        'formalDate' => CarbonImmutable::now()->subDays(10)->startOfDay()->toDateTimeImmutable(),
                        'dossier' => $covenant,
                    ])
            );
        }
        $this->addToPool('attachments', $covenantAttachments);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::create(year: 2023, month: 3, day: $this->_uuid_increment++, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
