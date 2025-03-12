<?php

namespace App\Tests\Factory;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Service\Storage\StorageRootPathGenerator;
use App\SourceType;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ProductionReport>
 */
final class ProductionReportFactory extends PersistentProxyObjectFactory
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
        $fileName = 'publicatierapport-1234.xlsx';

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
        return $this->afterInstantiate(function (ProductionReport $productionReport): void {
            $productionReport
                ->getFileInfo()
                ->setPath(sprintf(
                    '%s/%s',
                    $this->storageRootPathGenerator->fromUuid($productionReport->getId()),
                    $productionReport->getFileInfo()->getName(),
                ));
        });
    }

    public static function class(): string
    {
        return ProductionReport::class;
    }
}
