<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Alert
{
    public bool $hasRole = false;
    public string $type;

    public function mount(string $type): void
    {
        $validTypes = ['danger', 'info', 'success'];
        $this->type = in_array($type, $validTypes) ? $type : 'success';
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'danger' => 'warning',
            'info' => 'info',
            'success' => 'check',
            default => 'check',
        };
    }

    public function getAlertType(): string
    {
        return match ($this->type) {
            'danger' => 'woo-alert--danger',
            'info' => 'woo-alert--info',
            'success' => 'woo-alert--success',
            default => 'woo-alert--success',
        };
    }
}
