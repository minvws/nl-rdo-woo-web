<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Alert
{
    public ?string $class = '';
    public ?string $type = '';
    public bool $hasRole = false;

    public function mount(string $type): void
    {
        $validTypes = ['danger', 'info', 'success'];
        $this->type = in_array($type, $validTypes) ? $type : 'success';
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'danger' => 'exclamation-filled-colored',
            'info' => 'info-rounded-filled',
            default => 'check-rounded-filled',
        };
    }

    public function getIconColor(): string
    {
        return match ($this->type) {
            'danger' => 'fill-current',
            'info' => 'fill-bhr-blue-800',
            default => 'fill-bhr-philippine-green',
        };
    }

    public function getAlertType(): string
    {
        return match ($this->type) {
            'danger' => 'bhr-alert--danger',
            'info' => 'bhr-alert--info',
            default => 'bhr-alert--success',
        };
    }
}
