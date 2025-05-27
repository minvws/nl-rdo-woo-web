<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\CspExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Global twig extensions that are CSP specific.
 */
class CspExtension extends AbstractExtension
{
    public function __construct(protected CspExtensionRuntime $runtime)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->runtime->getCspNonce(...)),
        ];
    }
}
