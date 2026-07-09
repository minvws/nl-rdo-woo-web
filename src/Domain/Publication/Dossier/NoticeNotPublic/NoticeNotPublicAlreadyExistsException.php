<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

use RuntimeException;

class NoticeNotPublicAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This dossier already has a notice that it is not public');
    }
}
