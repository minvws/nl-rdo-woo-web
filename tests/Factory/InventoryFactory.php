<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use DateTimeImmutable;
use Override;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Storage\StorageRootPathGenerator;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function sprintf;

/**
 * @extends PersistentObjectFactory<Inventory>
 */
final class InventoryFactory extends PersistentObjectFactory
{
    public function __construct(private readonly StorageRootPathGenerator $storageRootPathGenerator)
    {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array|callable
    {
        $fileName = 'inventarislijst-1234.xlsx';

        return [
            'dossier' => WooDecisionFactory::new(),
            'fileInfo' => FileInfoFactory::new([
                'sourceType' => SourceType::SPREADSHEET,
                'name' => $fileName,
                'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'type' => 'xlsx',
                'uploaded' => true,
                'path' => sprintf('%s/%s', $this->storageRootPathGenerator->fromUuid(Uuid::v6()), $fileName),
                'size' => 1337,
            ]),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Inventory $inventory): void {
            $inventory
                ->getFileInfo()
                ->setPath(sprintf(
                    '%s/%s',
                    $this->storageRootPathGenerator->fromUuid($inventory->getId()),
                    $inventory->getFileInfo()->getName(),
                ));
        });
    }

    public static function class(): string
    {
        return Inventory::class;
    }
}
