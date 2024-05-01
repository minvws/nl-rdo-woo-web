<?php

namespace App\Tests\Factory\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CovenantAttachment>
 *
 * @method        CovenantAttachment|Proxy                     create(array|callable $attributes = [])
 * @method static CovenantAttachment|Proxy                     createOne(array $attributes = [])
 * @method static CovenantAttachment|Proxy                     find(object|array|mixed $criteria)
 * @method static CovenantAttachment|Proxy                     findOrCreate(array $attributes)
 * @method static CovenantAttachment|Proxy                     first(string $sortedField = 'id')
 * @method static CovenantAttachment|Proxy                     last(string $sortedField = 'id')
 * @method static CovenantAttachment|Proxy                     random(array $attributes = [])
 * @method static CovenantAttachment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static CovenantAttachmentRepository|RepositoryProxy repository()
 * @method static CovenantAttachment[]|Proxy[]                 all()
 * @method static CovenantAttachment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static CovenantAttachment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static CovenantAttachment[]|Proxy[]                 findBy(array $attributes)
 * @method static CovenantAttachment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static CovenantAttachment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<CovenantAttachment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<CovenantAttachment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<CovenantAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<CovenantAttachment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<CovenantAttachment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<CovenantAttachment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<CovenantAttachment> random(array $attributes = [])
 * @phpstan-method static Proxy<CovenantAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<CovenantAttachment> repository()
 * @phpstan-method static list<Proxy<CovenantAttachment>> all()
 * @phpstan-method static list<Proxy<CovenantAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<CovenantAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<CovenantAttachment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<CovenantAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<CovenantAttachment>> randomSet(int $number, array $attributes = [])
 */
final class CovenantAttachmentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(AttachmentType::cases()),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'grounds' => self::faker()->optional(default: [])->words(),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return CovenantAttachment::class;
    }
}
