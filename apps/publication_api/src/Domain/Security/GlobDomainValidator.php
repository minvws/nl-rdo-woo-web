<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Security;

use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;
use function strtolower;

class GlobDomainValidator
{
    /**
     * @param array<array-key,string> $whitelist
     */
    public function isValid(array $whitelist, string $domain): bool
    {
        foreach ($whitelist as $pattern) {
            $regex = sprintf('/^%s$/i', str_replace('\*', '.*', preg_quote(strtolower($pattern), '/')));

            if (preg_match($regex, strtolower($domain))) {
                return true;
            }
        }

        return false;
    }
}
