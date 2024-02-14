<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizationEntryRequestStore
{
    private const REQUEST_ATTRIBUTE = 'auth_matrix';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \RuntimeException('No request available.');
        }

        if (! $request->attributes->has(self::REQUEST_ATTRIBUTE)) {
            throw new \RuntimeException('No auth matrix attrib available in the request');
        }

        /** @var Entry[] */
        return $request->attributes->get(self::REQUEST_ATTRIBUTE);
    }

    public function storeEntries(Entry ...$entries): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (! $request instanceof Request) {
            return false;
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $entries);

        return true;
    }
}
