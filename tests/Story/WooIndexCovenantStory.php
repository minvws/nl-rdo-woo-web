<?php

declare(strict_types=1);

namespace App\Tests\Story;

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

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('06626570-e581-11ef-82b2-09e15b3f6ce0');
    }

    public function build(): void
    {
        $covenant = CarbonImmutable::withTestNow(
            CarbonImmutable::parse('2024-03-10 05:37:42'),
            fn () => CovenantFactory::createOne(['dossierNr' => 'my-covenant-1']),
        );
        $this->addState('covenant', $covenant);

        $covenantMainDocument = CarbonImmutable::withTestNow(
            CarbonImmutable::create('2012-04-04 01:12:42'),
            fn () => CovenantMainDocumentFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                ->create([
                    'overwrite_id' => $this->getUuid(1),
                    'formalDate' => CarbonImmutable::now()->subDays(5),
                    'dossier' => $covenant,
                ]),
        );
        $this->addState('mainDocument', $covenantMainDocument);

        $covenantAttachments = [];
        foreach (range(1, 3) as $i) {
            $attachment = CarbonImmutable::withTestNow(
                CarbonImmutable::create(year: 2010, month: 5, day: $i, hour: 13, minute: 37, second: 42),
                function () use ($i, $covenant) {
                    $document = CovenantAttachmentFactory::new()
                        ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                        ->create([
                            'overwrite_id' => $this->getUuid($i),
                            'formalDate' => CarbonImmutable::now()->subDays(10),
                            'dossier' => $covenant,
                        ]);

                    return $document;
                },
            );

            $covenantAttachments[] = $attachment;
        }
        $this->addToPool('attachments', $covenantAttachments);
    }

    private function getUuid(int $day): UuidV6
    {
        $date = CarbonImmutable::create(year: 2024, month: 5, day: $day, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
