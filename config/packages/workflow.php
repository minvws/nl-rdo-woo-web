<?php

declare(strict_types=1);

use App\Domain\Publication\Dossier\Type\Covenant\CovenantWorkflow;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionWorkflow;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig): void {
    WooDecisionWorkflow::configure($frameworkConfig);
    CovenantWorkflow::configure($frameworkConfig);
};
