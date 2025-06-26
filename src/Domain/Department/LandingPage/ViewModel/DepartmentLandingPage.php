<?php

declare(strict_types=1);

namespace App\Domain\Department\LandingPage\ViewModel;

final readonly class DepartmentLandingPage
{
    public function __construct(
        public string $name,
        public string $deleteLogoEndpoint,
        public ?string $logoEndpoint,
        public string $uploadLogoEndpoint,
    ) {
    }
}
