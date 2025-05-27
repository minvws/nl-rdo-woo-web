<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use App\Service\Storage\StorageRootPathGenerator;
use App\Tests\Factory\FileInfoFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<WooDecisionMainDocument>
 */
final class WooDecisionMainDocumentFactory extends PersistentProxyObjectFactory
{
    public function __construct(
        private readonly StorageRootPathGenerator $storageRootPathGenerator,
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
            'dossier' => WooDecisionFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'grounds' => self::faker()->optional(default: [])->words(),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'type' => self::faker()->randomElement(WooDecisionMainDocument::getAllowedTypes()),
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
            ->afterInstantiate(function (WooDecisionMainDocument $document, array $attributes): void {
                $document
                    ->getFileInfo()
                    ->setPath(sprintf(
                        '%s/%s',
                        $this->storageRootPathGenerator->fromUuid($document->getId()),
                        $document->getFileInfo()->getName(),
                    ));

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
        return WooDecisionMainDocument::class;
    }
}
