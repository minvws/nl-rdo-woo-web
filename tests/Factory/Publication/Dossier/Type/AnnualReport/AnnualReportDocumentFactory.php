<?php

namespace App\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<AnnualReportDocument>
 *
 * @method        AnnualReportDocument|Proxy                     create(array|callable $attributes = [])
 * @method static AnnualReportDocument|Proxy                     createOne(array $attributes = [])
 * @method static AnnualReportDocument|Proxy                     find(object|array|mixed $criteria)
 * @method static AnnualReportDocument|Proxy                     findOrCreate(array $attributes)
 * @method static AnnualReportDocument|Proxy                     first(string $sortedField = 'id')
 * @method static AnnualReportDocument|Proxy                     last(string $sortedField = 'id')
 * @method static AnnualReportDocument|Proxy                     random(array $attributes = [])
 * @method static AnnualReportDocument|Proxy                     randomOrCreate(array $attributes = [])
 * @method static AnnualReportDocumentRepository|RepositoryProxy repository()
 * @method static AnnualReportDocument[]|Proxy[]                 all()
 * @method static AnnualReportDocument[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static AnnualReportDocument[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static AnnualReportDocument[]|Proxy[]                 findBy(array $attributes)
 * @method static AnnualReportDocument[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static AnnualReportDocument[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<AnnualReportDocument> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<AnnualReportDocument> createOne(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReportDocument> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<AnnualReportDocument> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<AnnualReportDocument> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReportDocument> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReportDocument> random(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReportDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<AnnualReportDocument> repository()
 * @phpstan-method static list<Proxy<AnnualReportDocument>> all()
 * @phpstan-method static list<Proxy<AnnualReportDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReportDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<AnnualReportDocument>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<AnnualReportDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReportDocument>> randomSet(int $number, array $attributes = [])
 */
final class AnnualReportDocumentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(AnnualReportDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(AnnualReportDocument $AnnualReportDocument): void {})
        ;
    }

    protected static function getClass(): string
    {
        return AnnualReportDocument::class;
    }
}
