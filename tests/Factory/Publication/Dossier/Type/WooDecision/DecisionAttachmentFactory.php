<?php

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Entity\DecisionAttachment;
use App\Tests\Factory\FileInfoFactory;

/**
 * @method        \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   create(array|callable $attributes = [])
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   createOne(array $attributes = [])
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   find(object|array|mixed $criteria)
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   findOrCreate(array $attributes)
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   first(string $sortedField = 'id')
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   last(string $sortedField = 'id')
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   random(array $attributes = [])
 * @method static \App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy                                                                   randomOrCreate(array $attributes = [])
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               all()
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               createSequence(iterable|callable $sequence)
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               findBy(array $attributes)
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\DecisionAttachment[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                               randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy>                             many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\DecisionAttachment|\Zenstruck\Foundry\Persistence\Proxy>                             sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\DecisionAttachment, \App\Repository\DecisionAttachmentRepository> repository()
 *
 * @phpstan-method \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> random(array $attributes = [])
 * @phpstan-method static \App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> all()
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\DecisionAttachment&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\DecisionAttachment>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\DecisionAttachment>
 */
final class DecisionAttachmentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
            'dossier' => WooDecisionFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'type' => self::faker()->randomElement(DecisionAttachment::getAllowedTypes()),
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
        return $this
            // ->afterInstantiate(function(DecisionAttachment $decisionAttachment): void {})
        ;
    }

    public static function class(): string
    {
        return DecisionAttachment::class;
    }
}
