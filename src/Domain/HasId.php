<?php

declare(strict_types=1);

namespace Shared\Domain;

use Symfony\Component\Uid\Uuid;

interface HasId
{
    public function getId(): Uuid;
}
