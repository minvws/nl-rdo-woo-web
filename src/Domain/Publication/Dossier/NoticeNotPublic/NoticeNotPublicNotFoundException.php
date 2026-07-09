<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

use RuntimeException;

class NoticeNotPublicNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This dossier has no notice that it is not public');
    }
}
