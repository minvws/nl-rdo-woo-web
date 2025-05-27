<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class InputErrors
{
    public ?string $id = null;
    public ?string $error = null;
    public ?FormErrorIterator $errors = null;
}
