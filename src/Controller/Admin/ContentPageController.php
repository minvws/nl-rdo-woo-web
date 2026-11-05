<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
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
    public const string ROUTE_NAME_CONTENT_PAGES = 'app_admin_content_pages';
    public const string ROUTE_NAME_CONTENT_PAGE_EDIT = 'app_admin_content_page_edit';

    public function __construct(
        private readonly ContentPageRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/admin/content-pages', self::ROUTE_NAME_CONTENT_PAGES, methods: ['GET'])]
    #[IsGranted('AuthMatrix.content_page.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('admin.content_pages.manage');

        return $this->render('admin/content-page/index.html.twig', [
            'pages' => $this->repository->findAllSortedBySlug(),
        ]);
    }

    #[Route('/balie/admin/content-pages/{slug}', self::ROUTE_NAME_CONTENT_PAGE_EDIT, methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.content_page.update')]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        ContentPage $contentPage,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.content_pages.manage', 'app_admin_content_pages');
        $breadcrumbs->addItem('admin.global.edit');

        $form = $this->createForm(ContentPageType::class, $contentPage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($contentPage, true);
            $this->addFlash('backend', ['success' => $this->translator->trans('admin.content-page.modified')]);

            return $this->redirectToRoute('app_admin_content_pages');
        }

        return $this->render('admin/content-page/edit.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'page' => $contentPage,
            'form' => $form,
        ]);
    }
}
