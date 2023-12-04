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
            'danger' => 'exclamation-colored',
            'info' => 'info-rounded-filled',
            'success' => 'check-rounded-filled',
            default => 'check',
        };
    }

    public function getIconColor(): string
    {
        return match ($this->type) {
            'danger' => 'fill-current',
            'info' => 'fill-ocean-boat-blue',
            'success' => 'fill-philippine-green',
            default => 'check',
        };
    }

    public function getAlertType(): string
    {
        return match ($this->type) {
            'danger' => 'bhr-alert--danger',
            'info' => 'bhr-alert--info',
            'success' => 'bhr-alert--success',
            default => 'bhr-alert--success',
        };
    }
}
