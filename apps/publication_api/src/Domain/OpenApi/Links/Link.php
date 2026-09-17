<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Links;

use JsonSerializable;
use Shared\ValueObject\Url;

readonly class Link implements JsonSerializable
{
    public function __construct(
        public Url $href,
        public ?string $name = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'href' => $this->href->toString(),
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        return $data;
    }
}
