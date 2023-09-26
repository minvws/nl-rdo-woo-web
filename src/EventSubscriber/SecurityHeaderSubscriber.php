<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will add security headers to the response.
 */
class SecurityHeaderSubscriber implements EventSubscriberInterface
{
    protected string $appMode;

    protected const CSP_SELF = "'self'";
    protected const CSP_UNSAFE_INLINE = "'unsafe-inline'";
    protected const CSP_UNSAFE_EVAL = "'unsafe-eval'";
    protected const CSP_DATA = 'data:';

    /** @var array|string[] */
    protected array $fields = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'same-origin',
        'Permissions-Policy' => 'accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), ' .
            'cross-origin-isolated=(), display-capture=(), document-domain=(), encrypted-media=(), ' .
            'execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=(self), ' .
            'geolocation=(), gyroscope=(), keyboard-map=(), magnetometer=(), microphone=(), midi=(), ' .
            'navigation-override=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), ' .
            'screen-wake-lock=(), sync-xhr=(), usb=(), web-share=(), xr-spatial-tracking=()',
        'X-Dns-Prefetch-Control' => 'off',
        'X-Download-Options' => 'noopen',
        'X-Permitted-Cross-Domain-Policies' => 'off',
        'X-XSS-Protection' => '1; mode=block',
    ];

    /** @var array|string[][][] */
    protected array $csp = [
        'FRONTEND' => [
            'default-src' => [self::CSP_SELF],
            'frame-ancestors' => [self::CSP_SELF],
            'form-action' => [self::CSP_SELF],
            'base-uri' => [self::CSP_SELF],
            'connect-src' => [self::CSP_SELF, 'https://statistiek.rijksoverheid.nl'],
            'script-src' => [self::CSP_SELF, 'https://statistiek.rijksoverheid.nl'],
            'style-src' => [self::CSP_SELF],
            'img-src' => [self::CSP_SELF, self::CSP_DATA, 'https://statistiek.rijksoverheid.nl'],
            'font-src' => [self::CSP_SELF],
        ],
        'BALIE' => [
            'default-src' => [self::CSP_SELF],
            'frame-ancestors' => [self::CSP_SELF],
            'form-action' => [self::CSP_SELF],
            'base-uri' => [self::CSP_SELF],
            'connect-src' => [self::CSP_SELF, 'https://statistiek.rijksoverheid.nl'],
            'script-src' => [self::CSP_SELF, self::CSP_UNSAFE_INLINE, self::CSP_UNSAFE_EVAL, 'https://statistiek.rijksoverheid.nl'],
            'style-src' => [self::CSP_SELF, self::CSP_UNSAFE_INLINE],
            'img-src' => [self::CSP_SELF, self::CSP_DATA, 'https://statistiek.rijksoverheid.nl'],
            'font-src' => [self::CSP_SELF],
        ],
        'BOTH' => [
            'default-src' => [self::CSP_SELF],
            'frame-ancestors' => [self::CSP_SELF],
            'form-action' => [self::CSP_SELF],
            'base-uri' => [self::CSP_SELF],
            'connect-src' => [self::CSP_SELF, 'https://statistiek.rijksoverheid.nl'],
            'script-src' => [self::CSP_SELF, self::CSP_UNSAFE_INLINE, self::CSP_UNSAFE_EVAL, 'https://statistiek.rijksoverheid.nl'],
            'style-src' => [self::CSP_SELF, self::CSP_UNSAFE_INLINE],
            'img-src' => [self::CSP_SELF, self::CSP_DATA],
            'font-src' => [self::CSP_SELF],
        ],
    ];

    public function __construct(string $appMode)
    {
        $this->appMode = $appMode;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Add random nonce that can be used in CSP for this request only
        $nonce = bin2hex(random_bytes(16));

        $event->getRequest()->attributes->set('csp_nonce', $nonce);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        foreach ($this->fields as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // Add nonce to CSP
        $nonce = $event->getRequest()->attributes->get('csp_nonce');

        $csp = $this->csp[$this->appMode] ?? $this->csp['BOTH'];
        $csp['script-src'][] = "'nonce-" . $nonce . "'";
        $csp['style-src'][] = "'nonce-" . $nonce . "'";

        $response->headers->set('Content-Security-Policy', $this->buildCsp($csp));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param string[][] $csp
     */
    protected function buildCsp(array $csp): string
    {
        $result = [];
        foreach ($csp as $key => $value) {
            $result[] = $key . ' ' . join(' ', $value);
        }

        return implode('; ', $result);
    }
}
