<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Department\DepartmentService;
use App\Domain\Department\LandingPage\ViewModel\DepartmentLandingPageViewFactory;
use App\Form\LandingPageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentLandingPageController extends AbstractController
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly TranslatorInterface $translator,
        private readonly DepartmentService $departmentService,
        private readonly DepartmentLandingPageViewFactory $viewFactory,
    ) {
    }

    #[Route('/balie/bestuursorganen/{id}/landingpage', name: 'app_admin_department_landing_page_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.department_landing_page.update')]
    public function edit(Request $request, Department $department): Response
    {
        if (! $this->departmentService->userCanEditLandingpage($department)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(LandingPageType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($department, true);
            $this->addFlash('backend', ['success' => $this->translator->trans('admin.department.landing_page.modified')]);

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/landing_page_edit.html.twig', [
            'department' => $this->viewFactory->make($department),
            'form' => $form->createView(),
        ]);
    }
}
