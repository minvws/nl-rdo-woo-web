<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DocumentPrefix;
use App\Form\DocumentPrefixType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class PrefixController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    #[Route('/balie/prefix', name: 'app_admin_prefixes', methods: ['GET'])]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Prefix management');

        $prefixes = $this->doctrine->getRepository(DocumentPrefix::class)->findAll();

        return $this->render('admin/prefix/index.html.twig', [
            'prefixes' => $prefixes,
        ]);
    }

    #[Route('/balie/prefix/new', name: 'app_admin_prefix_create', methods: ['GET', 'POST'])]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Prefix management', 'app_admin_prefixes');
        $breadcrumbs->addItem('New prefix');

        $prefix = new DocumentPrefix();
        $form = $this->createForm(DocumentPrefixType::class, $prefix);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->persist($prefix);
            $this->doctrine->flush();

            $this->addFlash('success', $this->translator->trans('Prefix created'));

            return $this->redirectToRoute('app_admin_prefixes');
        }

        return $this->render('admin/prefix/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/prefixes/{id}', name: 'app_admin_prefix_edit', methods: ['GET', 'POST'])]
    public function editPrefix(Breadcrumbs $breadcrumbs, Request $request, DocumentPrefix $prefix): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Prefix management', 'app_admin_prefixes');
        $breadcrumbs->addItem('Edit prefix');

        $form = $this->createForm(DocumentPrefixType::class, $prefix);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->flush();
            $this->addFlash('success', $this->translator->trans('Prefix modified'));

            return $this->redirectToRoute('app_admin_prefixes');
        }

        return $this->render('admin/prefix/edit.html.twig', [
            'prefix' => $prefix,
            'form' => $form->createView(),
        ]);
    }
}
