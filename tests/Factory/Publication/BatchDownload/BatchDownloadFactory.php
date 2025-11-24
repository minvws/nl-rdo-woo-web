<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\BatchDownload;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<BatchDownload>
 */
final class BatchDownloadFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'expiration' => new \DateTimeImmutable('+1 month'),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return BatchDownload::class;
    }
}
