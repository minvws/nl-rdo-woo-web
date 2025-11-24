<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Gpt;

use Shared\Domain\Content\Page\ContentPageService;
use Shared\Domain\Content\Page\ContentPageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    public function __construct(
        private readonly ContentPageService $contentPageService,
    ) {
    }

    #[Route('/woo-gpt/over', name: 'app_gpt_about', methods: ['GET'], condition: "env('HAS_FEATURE_WOO_GPT') === 'true'")]
    public function index(): Response
    {
        return $this->render('public/gpt/about.html.twig', [
            'aboutGeneratedAnswersContent' => $this->contentPageService->getViewModel(ContentPageType::WOO_GPT_ABOUT_GENERATED_ANSWERS),
            'aboutPlatformContent' => $this->contentPageService->getViewModel(ContentPageType::WOO_GPT_ABOUT_PLATFORM),
        ]);
    }
}
