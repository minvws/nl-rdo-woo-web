<?php

declare(strict_types=1);

namespace Shared\Twig\Extension;

use Shared\Domain\Department\DepartmentService;
use Shared\Twig\Runtime\AuthExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Authorization extension.
 */
class AuthExtension extends AbstractExtension
{
    public function __construct(
        private readonly AuthExtensionRuntime $runtime,
        private readonly DepartmentService $departmentService,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('matrix_has_permission', $this->runtime->hasPermission(...)),
            new TwigFunction('user_can_edit_landingpage', $this->departmentService->userCanEditLandingpage(...)),
        ];
    }
}
