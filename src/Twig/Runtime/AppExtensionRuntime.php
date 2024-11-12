<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    protected string $projectPath;
    protected RequestStack $requestStack;

    public function __construct(string $projectPath, RequestStack $requestStack)
    {
        $this->projectPath = $projectPath;
        $this->requestStack = $requestStack;
    }

    public function size(string $value): string
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
}
