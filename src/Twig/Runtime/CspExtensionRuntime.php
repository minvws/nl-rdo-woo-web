<?php

declare(strict_types=1);

namespace Shared\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;
use Webmozart\Assert\Assert;

use function strval;

class CspExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    public function getCspNonce(): string
    {
        if ($this->requestStack->getCurrentRequest() == null) {
            return '';
        }

        $cspNonce = $this->requestStack->getCurrentRequest()->attributes->get('csp_nonce');
        Assert::nullOrScalar($cspNonce);

        return strval($cspNonce);
    }
}
