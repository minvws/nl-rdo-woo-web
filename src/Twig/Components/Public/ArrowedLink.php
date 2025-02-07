<?php

declare(strict_types=1);

namespace App\Twig\Components\Public;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ArrowedLink
{
    public ?string $prefix = '';
    public ?string $prefixSeparator = '|';
    public ?string $suffix = '';
    public string $to;
}
