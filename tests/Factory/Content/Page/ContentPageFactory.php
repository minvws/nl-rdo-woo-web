<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Content\Page;

use Shared\Domain\Content\Page\ContentPage;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ContentPage>
 */
final class ContentPageFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ContentPage::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array
    {
        return [
            'slug' => substr(self::faker()->unique()->slug(nbWords: 3), 0, 20),
            'title' => self::faker()->text(100),
            'content' => self::faker()->text(),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }
}
