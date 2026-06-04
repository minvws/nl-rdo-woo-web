<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

use function range;

final class WooIndexCovenantStory extends Story
{
    private readonly UuidV1 $v1Seed;

    private int $uuidIncrement = 1;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('06626570-e581-11ef-82b2-09e15b3f6ce0');
    }

    public function build(): void
    {
        $covenant = CovenantFactory::createOne([
            'dossierNr' => 'my-covenant-1',
            'createdAt' => CarbonImmutable::parse('2023-03-01 13:37:42'),
            'updatedAt' => CarbonImmutable::parse('2023-03-01 13:37:42'),
        ]);
        $this->addState('covenant', $covenant);

        $unpublishedCovenant = CovenantFactory::createOne([
            'status' => DossierStatus::NEW,
            'dossierNr' => 'my-unpublished-covenant-2',
            'createdAt' => CarbonImmutable::parse('2025-01-01'),
            'updatedAt' => CarbonImmutable::parse('2025-01-01'),
        ]);
        $this->addState('unpublishedCovenant', $unpublishedCovenant);

        $covenantMainDocument = CovenantMainDocumentFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
            ->create([
                'overwrite_id' => $this->getUniqueUuid(),
                'formalDate' => PlainDate::create('2023-02-27'),
                'dossier' => $covenant,
                'createdAt' => CarbonImmutable::parse('2023-03-04 01:12:42'),
                'updatedAt' => CarbonImmutable::parse('2023-03-04 01:12:42'),
            ]);
        $this->addState('mainDocument', $covenantMainDocument);

        $covenantAttachments = [];
        foreach (range(1, 3) as $i) {
            $covenantAttachments[] = CovenantAttachmentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUniqueUuid(),
                    'formalDate' => PlainDate::create('2023-02-18')->addDays($i),
                    'dossier' => $covenant,
                    'createdAt' => CarbonImmutable::parse('2023-02-28 13:37:42')->addDays($i),
                    'updatedAt' => CarbonImmutable::parse('2023-02-28 13:37:42')->addDays($i),
                ]);
        }
        $this->addToPool('attachments', $covenantAttachments);
    }

    private function getUniqueUuid(): UuidV6
    {
        $date = CarbonImmutable::parse('2023-02-28 13:37:42')->addDays($this->uuidIncrement++);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
