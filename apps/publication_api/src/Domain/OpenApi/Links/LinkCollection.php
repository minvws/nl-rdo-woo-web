<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Links;

use ArrayObject;
use JsonSerializable;

class LinkCollection implements JsonSerializable
{
    public const string FILE = 'file';
    public const string PUBLIC = 'public';
    public const string SELF = 'self';
    public const string UPLOAD = 'upload';

    /**
     * @var ArrayObject<string, Link>
     */
    private ArrayObject $links;

    public function __construct()
    {
        $this->links = new ArrayObject();
    }

    public function set(string $key, Link $link): void
    {
        $this->links[$key] = $link;
    }

    /**
     * @return ArrayObject<string, Link>
     */
    public function jsonSerialize(): ArrayObject
    {
        return $this->links;
    }
}
