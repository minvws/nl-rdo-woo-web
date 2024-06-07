<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<InvestigationReportAttachment>
 *
 * @method        InvestigationReportAttachment|Proxy                     create(array|callable $attributes = [])
 * @method static InvestigationReportAttachment|Proxy                     createOne(array $attributes = [])
 * @method static InvestigationReportAttachment|Proxy                     find(object|array|mixed $criteria)
 * @method static InvestigationReportAttachment|Proxy                     findOrCreate(array $attributes)
 * @method static InvestigationReportAttachment|Proxy                     first(string $sortedField = 'id')
 * @method static InvestigationReportAttachment|Proxy                     last(string $sortedField = 'id')
 * @method static InvestigationReportAttachment|Proxy                     random(array $attributes = [])
 * @method static InvestigationReportAttachment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static InvestigationReportAttachmentRepository|RepositoryProxy repository()
 * @method static InvestigationReportAttachment[]|Proxy[]                 all()
 * @method static InvestigationReportAttachment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static InvestigationReportAttachment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static InvestigationReportAttachment[]|Proxy[]                 findBy(array $attributes)
 * @method static InvestigationReportAttachment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static InvestigationReportAttachment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<InvestigationReportAttachment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportAttachment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportAttachment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<InvestigationReportAttachment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<InvestigationReportAttachment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReportAttachment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReportAttachment> random(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReportAttachment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<InvestigationReportAttachment> repository()
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> all()
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReportAttachment>> randomSet(int $number, array $attributes = [])
 */
final class InvestigationReportAttachmentFactory extends ModelFactory
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
            'type' => self::faker()->randomElement(InvestigationReportAttachment::getAllowedTypes()),
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
        return InvestigationReportAttachment::class;
    }
}
