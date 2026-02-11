<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Shared\Domain\Content\Page\ContentPage;
use Shared\Domain\Content\Page\ContentPageRepository;
use Shared\Form\ContentPageType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentPageController extends AbstractController
{
    public function __construct(
        private readonly ContentPageRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/admin/content-pages', name: 'app_admin_content_pages', methods: ['GET'])]
    #[IsGranted('AuthMatrix.content_page.read')]
    public function index(): Response
    {
        return $this->render('admin/content-page/index.html.twig', [
            'pages' => $this->repository->findAllSortedBySlug(),
        ]);
    }

    #[Route('/balie/admin/content-pages/{slug}', name: 'app_admin_content_page_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.content_page.update')]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        ContentPage $contentPage,
        Request $request,
    ): Response {
        $form = $this->createForm(ContentPageType::class, $contentPage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($contentPage, true);
            $this->addFlash('backend', ['success' => $this->translator->trans('admin.content-page.modified')]);

            return $this->redirectToRoute('app_admin_content_pages');
        }

        return $this->render('admin/content-page/edit.html.twig', [
            'page' => $contentPage,
            'form' => $form,
        ]);
    }
}
