<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use RuntimeException;

use function sprintf;

final class ProductionReportProcessRunCannotBeConfirmedException extends RuntimeException
{
    public function __construct(public readonly string $status)
    {
        parent::__construct(sprintf('Cannot confirm production report run with status %s', $status));
    }
}
