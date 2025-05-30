<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Service\Storage\StorageRootPathGenerator;
use App\Tests\Factory\FileInfoFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CovenantAttachment>
 */
final class CovenantAttachmentFactory extends PersistentProxyObjectFactory
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
            'dossier' => CovenantFactory::new(),
            'fileInfo' => FileInfoFactory::new(),
            'formalDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'type' => self::faker()->randomElement(CovenantAttachment::getAllowedTypes()),
            'internalReference' => self::faker()->optional(default: '')->words(asText: true),
            'language' => self::faker()->randomElement(AttachmentLanguage::cases()),
            'grounds' => self::faker()->optional(default: [])->words(),
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
            ->afterInstantiate(function (CovenantAttachment $attachment, array $attributes): void {
                $attachment
                    ->getFileInfo()
                    ->setPath(sprintf(
                        '%s/%s',
                        $this->storageRootPathGenerator->fromUuid($attachment->getId()),
                        $attachment->getFileInfo()->getName(),
                    ));

                if (isset($attributes['overwrite_id'])) {
                    $this->entityManager->detach($attachment);

                    $reflection = new \ReflectionClass($attachment);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($attachment, $attributes['overwrite_id']);

                    $this->entityManager->persist($attachment);
                }
            });
    }

    public static function class(): string
    {
        return CovenantAttachment::class;
    }
}
