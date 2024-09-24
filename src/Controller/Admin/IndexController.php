<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
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

    #[Route('/balie/contact', name: 'app_admin_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('admin/static/contact.html.twig', []);
    }

    #[Route('/balie/privacy', name: 'app_admin_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('admin/static/privacy.html.twig', []);
    }

    #[Route('/balie/toegankelijkheid', name: 'app_admin_accessibility', methods: ['GET'])]
    public function accessibility(): Response
    {
        return $this->render('admin/static/accessibility.html.twig', []);
    }
}
