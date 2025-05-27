<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Tests\Factory\WooIndex\WooIndexSitemapFactory;
use Carbon\CarbonImmutable;
use Zenstruck\Foundry\Story;

/**
 * @method static CarbonImmutable now()
 */
final class WooIndexSitemapsStory extends Story
{
    public function build(): void
    {
        $now = CarbonImmutable::parse('2024-03-13 13:37');
        $this->addState('now', $now);

        $finishedSitemaps = [];
        foreach (range(1, 10) as $i) {
            $finishedSitemaps[] = CarbonImmutable::withTestNow(
                $now->subDays($i),
                fn () => WooIndexSitemapFactory::new()->done()->create(),
            );
        }
        $this->addToPool('finishedSitemaps', $finishedSitemaps);

        $unfinishedSitemaps = [];
        foreach (range(1, 10) as $i) {
            $unfinishedSitemaps[] = CarbonImmutable::withTestNow(
                $now->subDays($i),
                fn () => WooIndexSitemapFactory::new()->processing()->create(),
            );
        }
        $this->addToPool('unfinishedSitemaps', $unfinishedSitemaps);
    }
}
