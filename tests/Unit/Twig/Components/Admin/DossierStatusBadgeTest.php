<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\DossierStatusBadge;

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
