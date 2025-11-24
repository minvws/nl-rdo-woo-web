<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Knp\Component\Pager\PaginatorInterface;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectService;
use Shared\Form\SubjectType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly TranslatorInterface $translator,
        private readonly SubjectService $subjectService,
    ) {
    }

    #[Route('/balie/onderwerpen', name: 'app_admin_subjects', methods: ['GET'])]
    #[IsGranted('AuthMatrix.subject.read')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            $this->subjectService->getSubjectsQueryForActiveOrganisation(),
            $request->query->getInt('page', 1),
            100,
        );

        return $this->render('admin/subjects/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/balie/onderwerpen/nieuw', name: 'app_admin_subject_create', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.subject.create')]
    public function create(Request $request): Response
    {
        $subject = $this->subjectService->createNew();
        $form = $this->createForm(SubjectType::class, $subject);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->subjectService->saveNew($subject);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.subject.created')]);

            return $this->redirectToRoute('app_admin_subjects');
        }

        return $this->render('admin/subjects/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/onderwerpen/{id}', name: 'app_admin_subject_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.subject.update')]
    public function modify(Request $request, Subject $subject): Response
    {
        $form = $this->createForm(SubjectType::class, $subject);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->subjectService->save($subject);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.subject.modified')]);

            return $this->redirectToRoute('app_admin_subjects');
        }

        return $this->render('admin/subjects/edit.html.twig', [
            'subject' => $subject,
            'form' => $form->createView(),
        ]);
    }
}
