<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class IconLink
{
    public ?string $href = null;
    public ?string $icon = null;
    public ?string $text = null;
}
