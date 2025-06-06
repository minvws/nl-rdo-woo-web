<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Tests\Factory\FileInfoFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<AnnualReportMainDocument>
 */
final class AnnualReportMainDocumentFactory extends PersistentProxyObjectFactory
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
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dossier' => AnnualReportFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'grounds' => self::faker()->optional(default: [])->words(),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'type' => self::faker()->randomElement(AnnualReportMainDocument::getAllowedTypes()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (AnnualReportMainDocument $document, array $attributes): void {
                if (isset($attributes['overwrite_id'])) {
                    $this->entityManager->detach($document);

                    $reflection = new \ReflectionClass($document);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
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
