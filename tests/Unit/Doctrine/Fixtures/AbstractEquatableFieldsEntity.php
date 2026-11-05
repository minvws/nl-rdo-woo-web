<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine\Fixtures;

use Shared\ValueObject\DossierTitle;

abstract class AbstractEquatableFieldsEntity
{
    private ?DossierTitle $inheritedTitle = null;

    public function getInheritedTitle(): ?DossierTitle
    {
        return $this->inheritedTitle;
    }

    public function setInheritedTitle(?DossierTitle $inheritedTitle): void
    {
        $this->inheritedTitle = $inheritedTitle;
    }
}
