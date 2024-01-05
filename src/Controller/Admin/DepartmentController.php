<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\Department;
use App\Form\DepartmentType;
use App\Message\UpdateDepartmentMessage;
use App\Repository\DepartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/departementen', name: 'app_admin_departments', methods: ['GET'])]
    #[AuthMatrix('department.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem('Department management');

        $departments = $this->repository->findAll();

        return $this->render('admin/departments/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    #[Route('/balie/departementen/new', name: 'app_admin_department_create', methods: ['GET', 'POST'])]
    #[AuthMatrix('department.create')]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('New department');

        $department = new Department();
        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($department, true);

            $this->addFlash('backend', ['success' => $this->translator->trans('Department created')]);

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/departementen/{id}', name: 'app_admin_department_edit', methods: ['GET', 'POST'])]
    #[AuthMatrix('department.update')]
    public function modify(Breadcrumbs $breadcrumbs, Request $request, Department $department): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('Edit department');

        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($department, true);
            $this->addFlash('backend', ['success' => $this->translator->trans('Department modified')]);

            $this->messageBus->dispatch(UpdateDepartmentMessage::forDepartment($department));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/edit.html.twig', [
            'department' => $department,
            'form' => $form->createView(),
        ]);
    }
}
