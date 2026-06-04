<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use ReflectionClass;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Tests\Factory\FileInfoFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function array_key_exists;

/**
 * @extends PersistentObjectFactory<AnnualReportMainDocument>
 */
final class AnnualReportMainDocumentFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dossier' => AnnualReportFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => self::faker()->plainDate(),
            'grounds' => self::faker()->optional(default: [])->words(),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'type' => self::faker()->randomElement(AnnualReportMainDocument::getAllowedTypes()),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (AnnualReportMainDocument $document, array $attributes): void {
                if (array_key_exists('overwrite_id', $attributes)) {
                    $this->entityManager->detach($document);

                    $reflection = new ReflectionClass($document);
                    $property = $reflection->getProperty('id');
                    $property->setValue($document, $attributes['overwrite_id']);

                    $this->entityManager->persist($document);
                }
            });
    }

    public static function class(): string
    {
        return AnnualReportMainDocument::class;
    }
}
