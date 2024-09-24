<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Icon
{
    public string $class = '';
    public string $color = 'fill-woo-dim-gray';
    public ?int $height = 0;
    public string $name = '';
    public string $path = 'assets/img/public/icons.svg';
    public ?int $size = 24;
    public ?int $width = 0;

    public function mount(
        ?int $height = 0,
        ?int $size = 24,
        ?int $width = 0,
    ): void {
        $this->size = $size;
        $this->height = $height !== 0 ? $height : $this->size;
        $this->width = $width !== 0 ? $width : $this->size;
    }
}
