<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Dialog
{
    /** @var array<string> */
    public ?array $dialogClasses = [];
    public string $id = '';
}
