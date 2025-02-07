<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class InputErrors
{
    public ?string $id;
    public ?string $error;
    public ?FormErrorIterator $errors;
}
