<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Exception;

use RuntimeException;
use Shared\Domain\WooIndex\WooIndexSitemap;
use Throwable;

use function sprintf;

final class WooIndexFileNotFoundException extends RuntimeException implements WooIndexException
{
    public static function create(WooIndexSitemap $wooIndexSitemap, string $file, ?Throwable $previous = null): self
    {
        return new self(sprintf(
            'Sitemap with id "%s" does not have a file named "%s"',
            $wooIndexSitemap->getId()->toRfc4122(),
            $file,
        ), previous: $previous);
    }
}
