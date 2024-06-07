<?php

namespace App\Tests\Factory\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CovenantDocument>
 *
 * @method        CovenantDocument|Proxy                     create(array|callable $attributes = [])
 * @method static CovenantDocument|Proxy                     createOne(array $attributes = [])
 * @method static CovenantDocument|Proxy                     find(object|array|mixed $criteria)
 * @method static CovenantDocument|Proxy                     findOrCreate(array $attributes)
 * @method static CovenantDocument|Proxy                     first(string $sortedField = 'id')
 * @method static CovenantDocument|Proxy                     last(string $sortedField = 'id')
 * @method static CovenantDocument|Proxy                     random(array $attributes = [])
 * @method static CovenantDocument|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CovenantDocumentRepository|RepositoryProxy repository()
 * @method static CovenantDocument[]|Proxy[]                 all()
 * @method static CovenantDocument[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CovenantDocument[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static CovenantDocument[]|Proxy[]                 findBy(array $attributes)
 * @method static CovenantDocument[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CovenantDocument[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<CovenantDocument> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<CovenantDocument> createOne(array $attributes = [])
 * @phpstan-method static Proxy<CovenantDocument> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<CovenantDocument> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<CovenantDocument> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<CovenantDocument> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<CovenantDocument> random(array $attributes = [])
 * @phpstan-method static Proxy<CovenantDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<CovenantDocument> repository()
 * @phpstan-method static list<Proxy<CovenantDocument>> all()
 * @phpstan-method static list<Proxy<CovenantDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<CovenantDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<CovenantDocument>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<CovenantDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<CovenantDocument>> randomSet(int $number, array $attributes = [])
 */
final class CovenantDocumentFactory extends ModelFactory
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
     * @todo add your default values here
     */
    protected function getDefaults(): array
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
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(CovenantDocument $covenantDocument): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CovenantDocument::class;
    }
}
