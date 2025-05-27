<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Exception;

use App\Domain\WooIndex\WooIndexSitemap;

final class WooIndexFileNotFoundException extends \RuntimeException implements WooIndexException
{
    public static function create(WooIndexSitemap $wooIndexSitemap, string $file, ?\Throwable $previous = null): self
    {
        return new self(sprintf(
            'Sitemap with id "%s" does not have a file named "%s"',
            $wooIndexSitemap->getId()->toRfc4122(),
            $file,
        ), previous: $previous);
    }
}
