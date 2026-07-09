<?php

declare(strict_types=1);

namespace PublicationApi\Api\Pagination;

use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @template T of object
 */
final readonly class CursorPage
{
    /**
     * @param list<T> $items
     * @param LinkCollection|null $halLinks HAL links; null means no _links key in output
     */
    public function __construct(
        public array $items,
        public bool $hasNextPage,
        #[SerializedName('_links')]
        public ?LinkCollection $halLinks = null,
    ) {
    }
}
