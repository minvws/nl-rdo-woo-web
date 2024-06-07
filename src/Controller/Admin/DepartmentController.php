<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Message\UpdateDepartmentMessage;
use App\Repository\DepartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/departementen', name: 'app_admin_departments', methods: ['GET'])]
    #[IsGranted('AuthMatrix.department.read')]
    public function index(): Response
    {
        $departments = $this->repository->findAll();

        return $this->render('admin/departments/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    #[Route('/balie/departementen/new', name: 'app_admin_department_create', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.department.create')]
    public function create(Request $request): Response
    {
        $department = new Department();
        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($department, true);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.department.created')]);

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/departementen/{id}', name: 'app_admin_department_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.department.update')]
    public function modify(Request $request, Department $department): Response
    {
        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($department, true);
            $this->addFlash('backend', ['success' => $this->translator->trans('admin.department.modified')]);

            $this->messageBus->dispatch(UpdateDepartmentMessage::forDepartment($department));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/edit.html.twig', [
            'department' => $department,
            'form' => $form->createView(),
        ]);
    }
}
