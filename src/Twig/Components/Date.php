<?php

declare(strict_types=1);

namespace Shared\Twig\Components;

use DateTimeImmutable;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Date
{
    public DateTimeImmutable|string $date;
}
