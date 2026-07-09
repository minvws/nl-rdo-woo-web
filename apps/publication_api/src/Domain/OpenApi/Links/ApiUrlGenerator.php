<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Links;

use Shared\ValueObject\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ApiUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function buildUrlFromRoute(string $name, array $parameters): Url
    {
        return Url::create($this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
