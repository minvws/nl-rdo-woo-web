<?php

declare(strict_types=1);

namespace Shared\Twig\Runtime;

use Shared\Service\EnvironmentService;
use Shared\Service\Utils\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

use function file_get_contents;
use function is_array;
use function json_decode;
use function str_starts_with;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly string $projectPath,
        private readonly RequestStack $requestStack,
        private readonly EnvironmentService $environmentService,
    ) {
    }

    public function size(string|int $value): string
    {
        return Utils::size($value);
    }

    /**
     * @return array<string, string>|mixed[]
     */
    public function appVersion(): array
    {
        $json = file_get_contents($this->projectPath . '/public/version.json');
        if ($json === false) {
            return [];
        }

        /** @var array<string,string> $version */
        $version = json_decode($json, true);

        return is_array($version) ? $version : [];
    }

    public function isBackend(): bool
    {
        $request = $this->requestStack->getCurrentRequest() ?? new Request();

        return str_starts_with($request->getPathInfo(), '/balie');
    }

    public function isDev(): bool
    {
        return $this->environmentService->isDev();
    }
}
