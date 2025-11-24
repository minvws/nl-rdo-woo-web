<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Public;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ArrowedBoldLink
{
    public string $to;
}
