<?php

namespace App\Tests\Factory\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Tests\Factory\FileInfoFactory;

/**
 * @method        \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               create(array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               createOne(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               find(object|array|mixed $criteria)
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               findOrCreate(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               first(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               last(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               random(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy                                                                                               randomOrCreate(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           all()
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           createMany(int $number, array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           createSequence(iterable|callable $sequence)
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           findBy(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                           randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy>                                                         many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument|\Zenstruck\Foundry\Persistence\Proxy>                                                         sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument, \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository> repository()
 *
 * @phpstan-method \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> create(array|callable $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> createOne(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> findOrCreate(array $attributes)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> first(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> last(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> random(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> all()
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> findBy(array $attributes)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument>
 */
final class CovenantDocumentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
            'grounds' => self::faker()->optional(default: [])->words(),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'type' => self::faker()->randomElement(CovenantDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(CovenantDocument $covenantDocument): void {})
        ;
    }

    public static function class(): string
    {
        return CovenantDocument::class;
    }
}
