<?php

namespace App\Tests\Factory;

use App\Entity\Document;
use App\Entity\Judgement;

/**
 * @method        \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         create(array|callable $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         createOne(array $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         find(object|array|mixed $criteria)
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         findOrCreate(array $attributes)
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         first(string $sortedField = 'id')
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         last(string $sortedField = 'id')
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         random(array $attributes = [])
 * @method static \App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy                                                         randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     all()
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     findBy(array $attributes)
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Document[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                     randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy>                   many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document|\Zenstruck\Foundry\Persistence\Proxy>                   sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Document, \App\Repository\DocumentRepository> repository()
 *
 * @phpstan-method \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> all()
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Document&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Document>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Document>
 */
final class DocumentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
     * @return array<string, mixed>
     */
    protected function defaults(): array
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
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Document $document): void {})
        ;
    }

    public static function class(): string
    {
        return Document::class;
    }
}
