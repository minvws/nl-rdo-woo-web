<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SubjectNameCheckController extends AbstractController
{
    public function __construct(
        private readonly SubjectRepository $subjectRepository,
    ) {
    }

    #[Route('/balie/data-checks/subject-name', name: 'app_admin_subject_name_check', methods: ['GET'])]
    #[IsGranted('AuthMatrix.data_check.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.data_checks.manage', 'app_admin_data_checks');
        $breadcrumbs->addItem('Onderwerp controle');

        return $this->render('admin/subject-name-check/index.html.twig', [
            'subjects' => $this->subjectRepository->getSubjectsWithDuplicateName(),
        ]);
    }
}
