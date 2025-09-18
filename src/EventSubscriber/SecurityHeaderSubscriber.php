<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

/**
 * This listener will add security headers to the response.
 */
class SecurityHeaderSubscriber implements EventSubscriberInterface
{
    private const string CSP_SELF = "'self'";
    private const string CSP_DATA = 'data:';
    private const string CSP_STATS = 'https://statistiek.rijksoverheid.nl';

    /** @var array|string[] */
    protected array $fields = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
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

    public function __construct(
        private readonly ApplicationMode $applicationMode,
    ) {
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

        $this->addSecurityHeaders($response);
        $this->addContentSecurityPolicy($event);
    }

    private function addSecurityHeaders(Response $response): void
    {
        foreach ($this->fields as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }
    }

    private function addContentSecurityPolicy(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $nonce = $request->attributes->get('csp_nonce');
        Assert::nullOrString($nonce);

        $csp = [
            'default-src' => [self::CSP_SELF],
            'frame-ancestors' => [self::CSP_SELF],
            'form-action' => [self::CSP_SELF],
            'base-uri' => [self::CSP_SELF],
            'connect-src' => [self::CSP_SELF, self::CSP_STATS],
            'script-src' => [self::CSP_SELF, self::CSP_STATS, "'nonce-" . $nonce . "'"],
            'style-src' => $this->getStyleSrcDirective($request, $nonce),
            'img-src' => [self::CSP_SELF, self::CSP_DATA],
            'font-src' => $this->getFontSrcDirective($request),
        ];

        if (! $this->applicationMode->isAll()) {
            $csp['img-src'] = [self::CSP_SELF, self::CSP_DATA, self::CSP_STATS];
        }

        $response->headers->set('Content-Security-Policy', $this->buildCsp($csp));
    }

    /**
     * @return list<string>
     */
    private function getStyleSrcDirective(Request $request, ?string $nonce): array
    {
        if ($this->isApiPlatformDocsRoute($request)) {
            return [self::CSP_SELF, "'unsafe-inline'"];
        }

        return [self::CSP_SELF, "'nonce-" . $nonce . "'"];
    }

    /**
     * @return list<string>
     */
    private function getFontSrcDirective(Request $request): array
    {
        if ($this->isScalarDocsRoute($request)) {
            return [self::CSP_SELF, 'https://fonts.scalar.com'];
        }

        return [self::CSP_SELF];
    }

    private function isApiPlatformDocsRoute(Request $request): bool
    {
        $requestUri = $request->getRequestUri();
        $routeName = $request->attributes->get('_route');

        return str_starts_with($requestUri, '/balie/api/docs')
               || (is_string($routeName) && $routeName === 'api_doc');
    }

    private function isScalarDocsRoute(Request $request): bool
    {
        $requestUri = $request->getRequestUri();
        $routeName = $request->attributes->get('_route');

        if (! ($this->applicationMode->isAll() || $this->applicationMode->isApi())) {
            return false;
        }

        return str_starts_with($requestUri, '/api/docs')
               || (is_string($routeName) && $routeName === 'app_api_docs');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param array<array-key,array<array-key,string>> $csp
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
