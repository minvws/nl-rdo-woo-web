<?php

declare(strict_types=1);

namespace Shared\Domain\Publication;

use Shared\ValueObject\Url;
use Symfony\Component\Routing\RouterInterface;

use function ltrim;
use function rtrim;
use function sprintf;

readonly class PublicUrlGenerator
{
    public function __construct(
        private string $publicBaseUrl,
        private RouterInterface $router,
    ) {
    }

    public function buildUrlFromPath(string $path): string
    {
        if ($path === '') {
            return $this->publicBaseUrl;
        }

        return sprintf('%s/%s', rtrim($this->publicBaseUrl, '/'), ltrim($path, '/'));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function buildUrlFromRoute(string $name, array $parameters): Url
    {
        $path = $this->router->generate($name, $parameters);
        $url = $this->buildUrlFromPath($path);

        return Url::create($url);
    }
}
