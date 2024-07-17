<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;

/**
 * @method        \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       create(array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       createOne(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       find(object|array|mixed $criteria)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       findOrCreate(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       first(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       last(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       random(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                                                       randomOrCreate(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   all()
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   createMany(int $number, array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   createSequence(iterable|callable $sequence)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   findBy(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                   randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy>                                                                 many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment|\Zenstruck\Foundry\Persistence\Proxy>                                                                 sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment, \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachmentRepository> repository()
 *
 * @phpstan-method \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> create(array|callable $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> createOne(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> findOrCreate(array $attributes)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> first(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> last(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> random(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> all()
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> findBy(array $attributes)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment>
 */
final class DispositionAttachmentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dossier' => CovenantFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'type' => self::faker()->randomElement(DispositionAttachment::getAllowedTypes()),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'grounds' => self::faker()->optional(default: [])->words(),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return DispositionAttachment::class;
    }
}
