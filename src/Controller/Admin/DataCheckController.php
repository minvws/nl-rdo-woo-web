<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DataCheckController extends AbstractController
{
    #[Route('/balie/data-checks', name: 'app_admin_data_checks', methods: ['GET'])]
    #[IsGranted('AuthMatrix.data_check.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('admin.data_checks.manage');

        return $this->render('admin/data-check/index.html.twig');
    }
}
