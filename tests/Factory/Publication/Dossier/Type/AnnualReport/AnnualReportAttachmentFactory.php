<?php

namespace App\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<AnnualReportAttachment>
 *
 * @method        AnnualReportAttachment|Proxy                     create(array|callable $attributes = [])
 * @method static AnnualReportAttachment|Proxy                     createOne(array $attributes = [])
 * @method static AnnualReportAttachment|Proxy                     find(object|array|mixed $criteria)
 * @method static AnnualReportAttachment|Proxy                     findOrCreate(array $attributes)
 * @method static AnnualReportAttachment|Proxy                     first(string $sortedField = 'id')
 * @method static AnnualReportAttachment|Proxy                     last(string $sortedField = 'id')
 * @method static AnnualReportAttachment|Proxy                     random(array $attributes = [])
 * @method static AnnualReportAttachment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static AnnualReportAttachmentRepository|RepositoryProxy repository()
 * @method static AnnualReportAttachment[]|Proxy[]                 all()
 * @method static AnnualReportAttachment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static AnnualReportAttachment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static AnnualReportAttachment[]|Proxy[]                 findBy(array $attributes)
 * @method static AnnualReportAttachment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static AnnualReportAttachment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<AnnualReportAttachment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<AnnualReportAttachment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReportAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<AnnualReportAttachment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<AnnualReportAttachment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReportAttachment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReportAttachment> random(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReportAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<AnnualReportAttachment> repository()
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> all()
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReportAttachment>> randomSet(int $number, array $attributes = [])
 */
final class AnnualReportAttachmentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(AnnualReportAttachment::getAllowedTypes()),
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
        return AnnualReportAttachment::class;
    }
}
