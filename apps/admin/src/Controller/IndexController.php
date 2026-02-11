<?php

declare(strict_types=1);

namespace Admin\Controller;

use Admin\Domain\Authentication\UserRouteHelper;
use Shared\Domain\Content\Page\ContentPage;
use Shared\Service\Security\Roles;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly UserRouteHelper $userRouteHelper,
    ) {
    }

    #[Route('/balie', name: 'app_admin_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute($this->userRouteHelper->getDefaultIndexRouteName());
    }

    #[Route('/balie/admin', name: 'app_admin', methods: ['GET'])]
    #[IsGranted(Roles::ROLE_SUPER_ADMIN)]
    public function admin(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('global.admin');

        return $this->render('admin/index.html.twig');
    }

    public function contentPage(
        #[MapEntity(mapping: ['slug' => 'slug'])] ContentPage $contentPage,
    ): Response {
        return $this->render('admin/content-page/render.html.twig', [
            'page' => $contentPage,
        ]);
    }
}
