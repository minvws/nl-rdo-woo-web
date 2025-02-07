<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Components\Admin;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Tests\Unit\UnitTestCase;
use App\Twig\Components\Admin\DossierStatusBadge;

final class DossierStatusBadgeTest extends UnitTestCase
{
    public function testGetBadgeColorCssClass(): void
    {
        $badgeColors = array_reduce(DossierStatus::cases(), function (array $carry, DossierStatus $status) {
            $badge = new DossierStatusBadge();
            $badge->status = $status;

            $carry[$status->name] = $badge->getBadgeColorCssClass();

            return $carry;
        }, []);

        ksort($badgeColors);

        $this->assertMatchesYamlSnapshot($badgeColors);
    }
}
