<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Definition
{
    public string $term = '';
}
