<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will add security headers to the response.
 */
class SecurityHeaderSubscriber implements EventSubscriberInterface
{
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
        'Content-Security-Policy' => "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: ; " .
            "font-src 'self';",
    ];

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        foreach ($this->fields as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
