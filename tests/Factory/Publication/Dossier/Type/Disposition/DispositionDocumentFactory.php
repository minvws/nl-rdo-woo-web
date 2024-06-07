<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DispositionDocument>
 *
 * @method        DispositionDocument|Proxy                     create(array|callable $attributes = [])
 * @method static DispositionDocument|Proxy                     createOne(array $attributes = [])
 * @method static DispositionDocument|Proxy                     find(object|array|mixed $criteria)
 * @method static DispositionDocument|Proxy                     findOrCreate(array $attributes)
 * @method static DispositionDocument|Proxy                     first(string $sortedField = 'id')
 * @method static DispositionDocument|Proxy                     last(string $sortedField = 'id')
 * @method static DispositionDocument|Proxy                     random(array $attributes = [])
 * @method static DispositionDocument|Proxy                     randomOrCreate(array $attributes = [])
 * @method static DispositionDocumentRepository|RepositoryProxy repository()
 * @method static DispositionDocument[]|Proxy[]                 all()
 * @method static DispositionDocument[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static DispositionDocument[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static DispositionDocument[]|Proxy[]                 findBy(array $attributes)
 * @method static DispositionDocument[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static DispositionDocument[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<DispositionDocument> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DispositionDocument> createOne(array $attributes = [])
 * @phpstan-method static Proxy<DispositionDocument> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<DispositionDocument> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<DispositionDocument> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<DispositionDocument> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<DispositionDocument> random(array $attributes = [])
 * @phpstan-method static Proxy<DispositionDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<DispositionDocument> repository()
 * @phpstan-method static list<Proxy<DispositionDocument>> all()
 * @phpstan-method static list<Proxy<DispositionDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<DispositionDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<DispositionDocument>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<DispositionDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<DispositionDocument>> randomSet(int $number, array $attributes = [])
 */
final class DispositionDocumentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(DispositionDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(DispositionDocument $DispositionDocument): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DispositionDocument::class;
    }
}
