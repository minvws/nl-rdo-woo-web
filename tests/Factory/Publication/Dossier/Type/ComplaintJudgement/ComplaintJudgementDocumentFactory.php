<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<ComplaintJudgementDocument>
 *
 * @method        ComplaintJudgementDocument|Proxy                     create(array|callable $attributes = [])
 * @method static ComplaintJudgementDocument|Proxy                     createOne(array $attributes = [])
 * @method static ComplaintJudgementDocument|Proxy                     find(object|array|mixed $criteria)
 * @method static ComplaintJudgementDocument|Proxy                     findOrCreate(array $attributes)
 * @method static ComplaintJudgementDocument|Proxy                     first(string $sortedField = 'id')
 * @method static ComplaintJudgementDocument|Proxy                     last(string $sortedField = 'id')
 * @method static ComplaintJudgementDocument|Proxy                     random(array $attributes = [])
 * @method static ComplaintJudgementDocument|Proxy                     randomOrCreate(array $attributes = [])
 * @method static ComplaintJudgementDocumentRepository|RepositoryProxy repository()
 * @method static ComplaintJudgementDocument[]|Proxy[]                 all()
 * @method static ComplaintJudgementDocument[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static ComplaintJudgementDocument[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static ComplaintJudgementDocument[]|Proxy[]                 findBy(array $attributes)
 * @method static ComplaintJudgementDocument[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static ComplaintJudgementDocument[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<ComplaintJudgementDocument> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgementDocument> createOne(array $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgementDocument> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<ComplaintJudgementDocument> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<ComplaintJudgementDocument> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<ComplaintJudgementDocument> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<ComplaintJudgementDocument> random(array $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgementDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<ComplaintJudgementDocument> repository()
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> all()
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<ComplaintJudgementDocument>> randomSet(int $number, array $attributes = [])
 */
final class ComplaintJudgementDocumentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(ComplaintJudgementDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(ComplaintJudgementDocument $ComplaintJudgementDocument): void {})
        ;
    }

    protected static function getClass(): string
    {
        return ComplaintJudgementDocument::class;
    }
}
