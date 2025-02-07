<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use App\Domain\Publication\History\History;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class DossierHistory
{
    /**
     * @var array<array-key,History>
     */
    public array $rows = [];
}
