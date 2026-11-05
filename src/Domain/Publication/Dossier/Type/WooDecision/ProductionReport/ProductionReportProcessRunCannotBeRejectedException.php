<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use RuntimeException;

use function sprintf;

final class ProductionReportProcessRunCannotBeRejectedException extends RuntimeException
{
    public function __construct(public readonly string $status)
    {
        parent::__construct(sprintf('Cannot reject production report run with status %s', $status));
    }
}
