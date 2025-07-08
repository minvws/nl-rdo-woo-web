<?php

declare(strict_types=1);

namespace App\Domain\Department\LandingPage\ViewModel;

use Symfony\Component\Uid\Uuid;

final readonly class DepartmentLandingPage
{
    public function __construct(
        public Uuid $departmentId,
        public string $name,
        public string $deleteLogoEndpoint,
        public string $logoEndpoint,
        public string $uploadLogoEndpoint,
        public bool $hasLogo,
    ) {
    }
}
