<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Icon
{
    public string $css = '';
    public string $color = 'fill-dim-gray';
    public int $dimensions = 24;
    public string $name = '';
}
