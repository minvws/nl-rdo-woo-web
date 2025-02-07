<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use App\Domain\Publication\Dossier\DossierStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class DossierStatusBadge
{
    public DossierStatus $status;

    public function getBadgeColorCssClass(): string
    {
        return match ($this->status) {
            DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED => 'bhr-badge--green',
            default => 'bhr-badge--purple',
        };
    }
}
