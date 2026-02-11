<?php

declare(strict_types=1);

namespace Admin\Controller;

use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\OrganisationSwitcher;
use Shared\Service\Security\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function in_array;

class OrganisationSwitchController extends AbstractController
{
    private const array ROUTE_WHITELIST = [
        'app_admin_dossiers',
        'app_admin_inquiries',
        'app_admin_departments',
        'app_admin_subjects',
        'app_admin_users',
    ];

    private const string FALLBACK_ROUTE = 'app_admin_index';

    public function __construct(
        private readonly OrganisationSwitcher $organisationSwitcher,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/balie/organisatie-wissel/{id}', name: 'app_admin_switch_organisation', methods: ['GET'])]
    #[IsGranted('AuthMatrix.organisation.read')]
    public function index(Request $request, Organisation $organisation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->organisationSwitcher->switchToOrganisation($user, $organisation);

        $referer = $request->headers->get('referer');
        if ($referer === null || $referer === '') {
            return $this->redirect(
                $this->generateUrl(self::FALLBACK_ROUTE)
            );
        }

        try {
            $refererPathInfo = Request::create($referer)->getPathInfo();
            $routeInfo = $this->router->match($refererPathInfo);
            $routeName = in_array($routeInfo['_route'], self::ROUTE_WHITELIST, true)
                ? $routeInfo['_route']
                : self::FALLBACK_ROUTE;
        } catch (ResourceNotFoundException) {
            $routeName = self::FALLBACK_ROUTE;
        }

        return $this->redirect(
            $this->generateUrl($routeName)
        );
    }
}
