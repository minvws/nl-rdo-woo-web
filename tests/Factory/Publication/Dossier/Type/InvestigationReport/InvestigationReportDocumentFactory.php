<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<InvestigationReportDocument>
 *
 * @method        InvestigationReportDocument|Proxy                     create(array|callable $attributes = [])
 * @method static InvestigationReportDocument|Proxy                     createOne(array $attributes = [])
 * @method static InvestigationReportDocument|Proxy                     find(object|array|mixed $criteria)
 * @method static InvestigationReportDocument|Proxy                     findOrCreate(array $attributes)
 * @method static InvestigationReportDocument|Proxy                     first(string $sortedField = 'id')
 * @method static InvestigationReportDocument|Proxy                     last(string $sortedField = 'id')
 * @method static InvestigationReportDocument|Proxy                     random(array $attributes = [])
 * @method static InvestigationReportDocument|Proxy                     randomOrCreate(array $attributes = [])
 * @method static InvestigationReportDocumentRepository|RepositoryProxy repository()
 * @method static InvestigationReportDocument[]|Proxy[]                 all()
 * @method static InvestigationReportDocument[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static InvestigationReportDocument[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static InvestigationReportDocument[]|Proxy[]                 findBy(array $attributes)
 * @method static InvestigationReportDocument[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static InvestigationReportDocument[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<InvestigationReportDocument> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportDocument> createOne(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportDocument> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<InvestigationReportDocument> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<InvestigationReportDocument> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReportDocument> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReportDocument> random(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportDocument> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<InvestigationReportDocument> repository()
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> all()
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReportDocument>> randomSet(int $number, array $attributes = [])
 */
final class InvestigationReportDocumentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(InvestigationReportDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(InvestigationReportDocument $InvestigationReportDocument): void {})
        ;
    }

    protected static function getClass(): string
    {
        return InvestigationReportDocument::class;
    }
}
