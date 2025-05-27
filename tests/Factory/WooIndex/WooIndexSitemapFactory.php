<?php

declare(strict_types=1);

namespace App\Tests\Factory\WooIndex;

use App\Domain\WooIndex\WooIndexSitemap;
use App\Domain\WooIndex\WooIndexSitemapStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<WooIndexSitemap>
 */
final class WooIndexSitemapFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return WooIndexSitemap::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array|callable
    {
        return [
            'status' => self::faker()->randomElement(WooIndexSitemapStatus::cases()),
        ];
    }

    public function done(): self
    {
        return $this->with([
            'status' => WooIndexSitemapStatus::DONE,
        ]);
    }

    public function processing(): self
    {
        return $this->with([
            'status' => WooIndexSitemapStatus::PROCESSING,
        ]);
    }
}
