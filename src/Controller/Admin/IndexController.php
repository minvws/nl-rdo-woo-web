<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Content\Page\ContentPage;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class IndexController extends AbstractController
{
    #[Route('/balie', name: 'app_admin_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_dossiers');
    }

    #[Route('/balie/admin', name: 'app_admin', methods: ['GET'])]
    public function admin(Breadcrumbs $breadcrumbs): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (! $user->hasRole('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('app_admin_dossiers');
        }

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('global.admin');

        return $this->render('admin/index.html.twig', []);
    }

    public function contentPage(
        #[MapEntity(mapping: ['slug' => 'slug'])] ContentPage $contentPage,
    ): Response {
        return $this->render('admin/content-page/render.html.twig', [
            'page' => $contentPage,
        ]);
    }
}
