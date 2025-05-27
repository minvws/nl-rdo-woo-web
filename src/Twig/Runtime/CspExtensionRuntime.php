<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

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

        return strval($this->requestStack->getCurrentRequest()->attributes->get('csp_nonce'));
    }
}
