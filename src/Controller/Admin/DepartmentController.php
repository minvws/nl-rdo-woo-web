<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Department\UpdateDepartmentCommand;
use App\Form\DepartmentType;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
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
    protected const int MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
        private readonly PaginatorInterface $paginator,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    #[Route('/balie/bestuursorganen', name: 'app_admin_departments', methods: ['GET'])]
    #[IsGranted('AuthMatrix.department.read')]
    public function index(Request $request): Response
    {
        $organisation = null;
        if ($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY)) {
            $organisation = $this->authorizationMatrix->getActiveOrganisation();
        }

        $pagination = $this->paginator->paginate(
            $this->repository->getDepartmentsQuery(filterByOrganisation: $organisation),
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

            $this->messageBus->dispatch(UpdateDepartmentCommand::forDepartment($department));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/edit.html.twig', [
            'department' => $department,
            'form' => $form->createView(),
        ]);
    }
}
