<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Links;

use JsonSerializable;
use Shared\ValueObject\Url;

readonly class Link implements JsonSerializable
{
    public function __construct(
        public Url $href,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href->toString(),
        ];
    }
}
