<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Links;

use ArrayObject;
use JsonSerializable;

use function array_merge;

class LinkCollection implements JsonSerializable
{
    public const string FILE = 'file';
    public const string INQUIRIES = 'inquiries';
    public const string PUBLIC = 'public';
    public const string SELF = 'self';
    public const string UPLOAD = 'upload';

    /**
     * @var array<string, Link>
     */
    private array $links = [];

    /**
     * @var array<string, list<Link>>
     */
    private array $linkLists = [];

    public function add(string $key, Link $link): void
    {
        $this->linkLists[$key][] = $link;
    }

    public function set(string $key, Link $link): void
    {
        $this->links[$key] = $link;
    }

    /**
     * @return ArrayObject<string, Link|list<Link>>
     */
    public function jsonSerialize(): ArrayObject
    {
        return new ArrayObject(array_merge($this->links, $this->linkLists));
    }
}
