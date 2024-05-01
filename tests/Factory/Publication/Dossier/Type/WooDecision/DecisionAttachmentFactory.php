<?php

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Entity\DecisionAttachment;
use App\Repository\DecisionAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DecisionAttachment>
 *
 * @method        DecisionAttachment|Proxy                     create(array|callable $attributes = [])
 * @method static DecisionAttachment|Proxy                     createOne(array $attributes = [])
 * @method static DecisionAttachment|Proxy                     find(object|array|mixed $criteria)
 * @method static DecisionAttachment|Proxy                     findOrCreate(array $attributes)
 * @method static DecisionAttachment|Proxy                     first(string $sortedField = 'id')
 * @method static DecisionAttachment|Proxy                     last(string $sortedField = 'id')
 * @method static DecisionAttachment|Proxy                     random(array $attributes = [])
 * @method static DecisionAttachment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static DecisionAttachmentRepository|RepositoryProxy repository()
 * @method static DecisionAttachment[]|Proxy[]                 all()
 * @method static DecisionAttachment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static DecisionAttachment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static DecisionAttachment[]|Proxy[]                 findBy(array $attributes)
 * @method static DecisionAttachment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static DecisionAttachment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<DecisionAttachment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DecisionAttachment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<DecisionAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<DecisionAttachment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<DecisionAttachment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<DecisionAttachment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<DecisionAttachment> random(array $attributes = [])
 * @phpstan-method static Proxy<DecisionAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<DecisionAttachment> repository()
 * @phpstan-method static list<Proxy<DecisionAttachment>> all()
 * @phpstan-method static list<Proxy<DecisionAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<DecisionAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<DecisionAttachment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<DecisionAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<DecisionAttachment>> randomSet(int $number, array $attributes = [])
 */
final class DecisionAttachmentFactory extends ModelFactory
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
            'dossier' => WooDecisionFactory::new(),
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
        return $this
            // ->afterInstantiate(function(DecisionAttachment $decisionAttachment): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DecisionAttachment::class;
    }
}
