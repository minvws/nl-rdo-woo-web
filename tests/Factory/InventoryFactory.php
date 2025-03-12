<?php

namespace App\Tests\Factory;

use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Service\Storage\StorageRootPathGenerator;
use App\SourceType;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Inventory>
 */
final class InventoryFactory extends PersistentProxyObjectFactory
{
    public function __construct(private StorageRootPathGenerator $storageRootPathGenerator)
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
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
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
