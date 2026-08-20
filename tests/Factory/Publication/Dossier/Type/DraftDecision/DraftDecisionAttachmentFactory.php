<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision;

use DateTimeImmutable;
use Override;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DraftDecisionAttachment>
 */
final class DraftDecisionAttachmentFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dossier' => DraftDecisionFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => self::faker()->plainDate(),
            'type' => self::faker()->randomElement(DraftDecisionAttachment::getAllowedTypes()),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'grounds' => self::faker()->optional(default: [])->words(),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return DraftDecisionAttachment::class;
    }
}
