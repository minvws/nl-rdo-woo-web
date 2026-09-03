<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Domain\Publication\Subject\Subject;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class SubjectLandingPageController extends AbstractController
{
    public function __construct(
        private readonly SubjectViewFactory $subjectViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/onderwerp/{slug}', name: 'app_subject_landing_page', methods: ['GET'])]
    public function detail(
        #[MapEntity(expr: 'repository.findPublishedLandingPageBySlug(slug)')]
        Subject $subject,
    ): Response {
        return $this->renderLandingPage($subject);
    }

    #[Route('/onderwerp/{id}/preview/{previewToken}', name: 'app_subject_landing_page_preview', methods: ['GET'])]
    public function preview(
        #[MapEntity(expr: 'repository.findConceptLandingPageByIdAndPreviewToken(id, previewToken)')]
        Subject $subject,
    ): Response {
        $response = $this->renderLandingPage($subject);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    private function renderLandingPage(Subject $subject): Response
    {
        return $this->render('public/subject/landing-page.html.twig', [
            'subject' => $this->subjectViewFactory->make($subject),
        ]);
    }
}
