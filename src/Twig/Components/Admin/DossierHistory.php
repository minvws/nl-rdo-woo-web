<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin;

use Shared\Domain\Publication\History\History;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class DossierHistory
{
    /**
     * @var array<array-key,History>
     */
    public array $rows = [];
}
