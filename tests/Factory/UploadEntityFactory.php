<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\InputBag;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function sprintf;

/**
 * @extends PersistentObjectFactory<UploadEntity>
 */
final class UploadEntityFactory extends PersistentObjectFactory
{
    /**
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

    public static function class(): string
    {
        return UploadEntity::class;
    }
}
