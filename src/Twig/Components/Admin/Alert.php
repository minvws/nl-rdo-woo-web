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
        $validTypes = ['danger', 'info', 'success', 'warning'];
        $this->type = in_array($type, $validTypes) ? $type : 'success';
    }

    public function getIconName(): string
    {
        return match ($this->type) {
            'danger' => 'alert-circle',
            'info' => 'info-circle',
            'warning' => 'alert-triangle',
            default => 'circle-check',
        };
    }

    public function getIconColor(): string
    {
        return match ($this->type) {
            'danger' => 'stroke-bhr-red-700',
            'info' => 'stroke-bhr-blue-700',
            'warning' => 'stroke-bhr-yellow-800',
            default => 'stroke-bhr-green-700',
        };
    }

    public function getAlertType(): string
    {
        return match ($this->type) {
            'danger' => 'bhr-alert--danger',
            'info' => 'bhr-alert--info',
            'warning' => 'bhr-alert--warning',
            default => 'bhr-alert--success',
        };
    }
}
