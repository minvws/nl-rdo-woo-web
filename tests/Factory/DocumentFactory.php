<?php

namespace App\Tests\Factory;

use App\Entity\Document;
use App\Entity\Judgement;
use App\Repository\DocumentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Document>
 *
 * @method        Document|Proxy                     create(array|callable $attributes = [])
 * @method static Document|Proxy                     createOne(array $attributes = [])
 * @method static Document|Proxy                     find(object|array|mixed $criteria)
 * @method static Document|Proxy                     findOrCreate(array $attributes)
 * @method static Document|Proxy                     first(string $sortedField = 'id')
 * @method static Document|Proxy                     last(string $sortedField = 'id')
 * @method static Document|Proxy                     random(array $attributes = [])
 * @method static Document|Proxy                     randomOrCreate(array $attributes = [])
 * @method static DocumentRepository|RepositoryProxy repository()
 * @method static Document[]|Proxy[]                 all()
 * @method static Document[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Document[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Document[]|Proxy[]                 findBy(array $attributes)
 * @method static Document[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Document[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Document> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Document> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Document> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Document> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Document> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Document> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Document> random(array $attributes = [])
 * @phpstan-method static Proxy<Document> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Document> repository()
 * @phpstan-method static list<Proxy<Document>> all()
 * @phpstan-method static list<Proxy<Document>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Document>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Document>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Document>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Document>> randomSet(int $number, array $attributes = [])
 */
final class DocumentFactory extends ModelFactory
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
        $documentId = self::faker()->unique()->randomNumber(nbDigits: 6, strict: true);
        $documentNr = sprintf('PREF-%s', $documentId);

        $judgement = self::faker()
            ->optional(0.5, Judgement::PUBLIC)
            ->randomElement([
                Judgement::PARTIAL_PUBLIC,
                Judgement::ALREADY_PUBLIC,
                Judgement::NOT_PUBLIC,
            ]);
        $uploaded = in_array($judgement, [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC], true);

        return [
            'documentDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'documentNr' => $documentNr,
            'familyId' => $documentId,
            'documentId' => (string) $documentId,
            'threadId' => 0,
            'pageCount' => self::faker()->numberBetween(1, 20),
            'summary' => self::faker()->paragraph(),
            'subjects' => self::faker()->words(self::faker()->numberBetween(1, 5)),
            'grounds' => self::faker()->groundsBetween(),
            'judgement' => $judgement,
            'fileInfo' => FileInfoFactory::new([
                'name' => 'document-' . $documentNr . '.pdf',
                'mimetype' => 'application/pdf',
                'type' => 'pdf',
                'uploaded' => $uploaded,
            ]),
            'links' => array_filter([$this->faker()->optional()->url()]),
            'remark' => $this->faker()->optional()->text(),

            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Document $document): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Document::class;
    }
}
