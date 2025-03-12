<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Carbon\CarbonImmutable;
use Symfony\Component\Uid\UuidV1;
use Symfony\Component\Uid\UuidV6;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Story;

final class WooIndexWooDecisionStory extends Story
{
    private readonly UuidV1 $v1Seed;

    public function __construct()
    {
        $this->v1Seed = UuidV1::fromString('c0785e96-de2f-11ef-b0e0-315d29be4a5c');
    }

    public function build(): void
    {
        $wooDecision = CarbonImmutable::withTestNow(
            CarbonImmutable::create(year: 2024, month: 6, day: 1, hour: 13, minute: 37, second: 42),
            fn () => WooDecisionFactory::createOne(['dossierNr' => 'mydossier-1']),
        );
        $this->addState('wooDecision', $wooDecision);

        $documents = [];
        foreach (range(1, 10) as $i) {
            $document = CarbonImmutable::withTestNow(
                CarbonImmutable::create(year: 2010, month: 5, day: $i, hour: 13, minute: 37, second: 42),
                function () use ($i, $wooDecision) {
                    $document = DocumentFactory::new()
                        ->instantiateWith(Instantiator::withConstructor()->allowExtra('overwrite_id'))
                        ->create([
                            'overwrite_id' => $this->getUuid($i),
                            'documentDate' => CarbonImmutable::now()->subYear()->subMonths(3),
                            'documentNr' => sprintf('%04d-%04d', 1, $i),
                            'dossiers' => [$wooDecision],
                        ]);

                    return $document;
                },
            );

            $documents[] = $document;
        }
        $this->addToPool('documents', $documents);
    }

    private function getUuid(int $day): UuidV6
    {
        $date = CarbonImmutable::create(year: 2024, month: 5, day: $day, hour: 13, minute: 37, second: 42);

        return new UuidV6(UuidV6::generate($date, $this->v1Seed));
    }
}
