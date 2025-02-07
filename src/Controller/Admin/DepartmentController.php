<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Message\UpdateDepartmentMessage;
use App\Repository\DepartmentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route('/balie/bestuursorganen', name: 'app_admin_departments', methods: ['GET'])]
    #[IsGranted('AuthMatrix.department.read')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            $this->repository->createQueryBuilder('d')->getQuery(),
            $request->query->getInt('page', 1),
            self::MAX_ITEMS_PER_PAGE,
            [
                PaginatorInterface::DEFAULT_SORT_FIELD_NAME => 'd.name',
            ],
        );

        return $this->render('admin/departments/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/balie/bestuursorganen/nieuw', name: 'app_admin_department_create', methods: ['GET', 'POST'])]
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

    #[Route('/balie/bestuursorganen/{id}', name: 'app_admin_department_edit', methods: ['GET', 'POST'])]
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
