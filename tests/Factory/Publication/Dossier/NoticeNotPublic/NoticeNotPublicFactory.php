<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\NoticeNotPublic;

use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<NoticeNotPublic>
 */
final class NoticeNotPublicFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'id' => Uuid::fromString(self::faker()->uuid()),
            'dossier' => CovenantFactory::new(),
            'documentName' => self::faker()->optional()->sentence(),
            'formalDate' => self::faker()->plainDateBetween('01-01-2010', '01-01-2023'),
            'grounds' => [self::faker()->word(), self::faker()->word()],
            'explanation' => self::faker()->optional()->sentence(),
        ];
    }

    public static function class(): string
    {
        return NoticeNotPublic::class;
    }
}
