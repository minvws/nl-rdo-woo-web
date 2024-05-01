<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

class DecisionAttachmentNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('DecisionAttachment not found');
    }
}
