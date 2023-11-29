<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Attribute\AuthMatrix;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will test if the user is authorized to access the requested route.
 */
class AuthMatrixSubscriber implements EventSubscriberInterface
{
    protected AuthorizationMatrix $matrix;

    public function __construct(AuthorizationMatrix $matrix)
    {
        $this->matrix = $matrix;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 20],
        ];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $attrs = $event->getAttributes();

        foreach ($attrs as $attr) {
            foreach ($attr as $item) {
                if (! $item instanceof AuthMatrix) {
                    continue;
                }

                $permission = $item->permission;
                list($prefix, $permission) = explode('.', $permission, 2);
                if (empty($permission)) {
                    throw new \RuntimeException('Permission must be in "prefix.permission" format.');
                }

                if ($this->matrix->isAuthorized($prefix, $permission)) {
                    // Add the authorized matches to the request attributes
                    $event->getRequest()->attributes->set(
                        AuthorizationMatrix::AUTH_MATRIX_ATTRIB,
                        $this->matrix->getAuthorizedMatches($prefix, $permission)
                    );

                    return;
                }

                throw new AccessDeniedHttpException('You are not authorized to access this resource.');
            }
        }

        $uri = $event->getRequest()->getRequestUri();

        // These routes are allowed not to have an AuthMatrix
        $allowedRoutes = [
            '/balie',
            '/balie/',
            '/balie/admin',
            '/balie/2fa',
            '/balie/2fa_check',
            '/balie/admin',
            '/balie/change-password',
            '/balie/contact',
            '/balie/login',
            '/balie/logout',
            '/balie/privacy',
        ];
        if (in_array($uri, $allowedRoutes)) {
            return;
        }

        // All other /balie routes should have an auth matrix
        if (! str_starts_with($uri, '/balie/')) {
            return;
        }

        throw new AccessDeniedHttpException('Please set AuthMatrix attribute on the controller.');
    }
}
