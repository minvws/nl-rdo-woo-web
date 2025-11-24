<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<AnnualReportAttachment>
 */
final class AnnualReportAttachmentFactory extends PersistentProxyObjectFactory
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
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (AnnualReportAttachment $attachment, array $attributes): void {
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
        return AnnualReportAttachment::class;
    }
}
