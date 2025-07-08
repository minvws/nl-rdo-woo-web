<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Upload\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\InputBag;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<UploadEntity>
 */
final class UploadEntityFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'uploadId' => sprintf('file-%s', self::faker()->uuid()),
            'uploadGroupId' => UploadGroupId::WOO_DECISION_DOCUMENTS,
            'context' => new InputBag(),
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
        return UploadEntity::class;
    }
}
