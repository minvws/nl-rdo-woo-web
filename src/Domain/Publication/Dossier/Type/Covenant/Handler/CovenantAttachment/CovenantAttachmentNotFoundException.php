<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantAttachment;

class CovenantAttachmentNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('CovenantAttachment not found');
    }
}
