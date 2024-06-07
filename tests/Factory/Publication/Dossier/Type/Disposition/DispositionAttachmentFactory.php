<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DispositionAttachment>
 *
 * @method        DispositionAttachment|Proxy                     create(array|callable $attributes = [])
 * @method static DispositionAttachment|Proxy                     createOne(array $attributes = [])
 * @method static DispositionAttachment|Proxy                     find(object|array|mixed $criteria)
 * @method static DispositionAttachment|Proxy                     findOrCreate(array $attributes)
 * @method static DispositionAttachment|Proxy                     first(string $sortedField = 'id')
 * @method static DispositionAttachment|Proxy                     last(string $sortedField = 'id')
 * @method static DispositionAttachment|Proxy                     random(array $attributes = [])
 * @method static DispositionAttachment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static DispositionAttachmentRepository|RepositoryProxy repository()
 * @method static DispositionAttachment[]|Proxy[]                 all()
 * @method static DispositionAttachment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static DispositionAttachment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static DispositionAttachment[]|Proxy[]                 findBy(array $attributes)
 * @method static DispositionAttachment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static DispositionAttachment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<DispositionAttachment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DispositionAttachment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<DispositionAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<DispositionAttachment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<DispositionAttachment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<DispositionAttachment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<DispositionAttachment> random(array $attributes = [])
 * @phpstan-method static Proxy<DispositionAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<DispositionAttachment> repository()
 * @phpstan-method static list<Proxy<DispositionAttachment>> all()
 * @phpstan-method static list<Proxy<DispositionAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<DispositionAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<DispositionAttachment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<DispositionAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<DispositionAttachment>> randomSet(int $number, array $attributes = [])
 */
final class DispositionAttachmentFactory extends ModelFactory
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
    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return DispositionAttachment::class;
    }
}
