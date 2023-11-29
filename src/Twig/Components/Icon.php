<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Icon
{
    public string $class = '';
    public string $color = 'fill-dim-gray';
    public string $name = '';
    public string $path = 'build/img/public/icons.svg';
    public int $size = 24;
}
