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
    protected CspExtensionRuntime $runtime;

    public function __construct(CspExtensionRuntime $runtime)
    {
        $this->runtime = $runtime;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', [$this->runtime, 'getCspNonce']),
        ];
    }
}
