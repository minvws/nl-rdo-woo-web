<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectService;
use Shared\Form\SubjectLandingPageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectLandingPageController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SubjectService $subjectService,
    ) {
    }

    #[Route('/balie/onderwerpen/{id}/landingpage', name: 'app_admin_subject_landing_page_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.subject.update')]
    public function edit(Request $request, Subject $subject): Response
    {
        $form = $this->createForm(SubjectLandingPageType::class, $subject);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->subjectService->save($subject);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.subject.modified')]);

            return $this->redirectToRoute('app_admin_subjects');
        }

        return $this->render('admin/subjects/landing_page_edit.html.twig', [
            'form' => $form,
            'subject' => $subject,
        ]);
    }
}
