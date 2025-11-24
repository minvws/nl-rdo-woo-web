<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\ProductionReport;

use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ProductionReportProcessRun>
 */
final class ProductionReportProcessRunFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array
    {
        return [
            'dossier' => WooDecisionFactory::new(),
        ];
    }

    public static function class(): string
    {
        return ProductionReportProcessRun::class;
    }
}
