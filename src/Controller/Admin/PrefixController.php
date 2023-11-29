<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\DocumentPrefix;
use App\Entity\User;
use App\Form\DocumentPrefixType;
use App\Service\Security\Authorization\AuthorizationMatrix;
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
    protected AuthorizationMatrix $authorizationMatrix;

    public function __construct(EntityManagerInterface $doctrine, TranslatorInterface $translator, AuthorizationMatrix $authorizationMatrix)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->authorizationMatrix = $authorizationMatrix;
    }

    #[Route('/balie/prefix', name: 'app_admin_prefixes', methods: ['GET'])]
    #[AuthMatrix('prefix.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Prefix management');

        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)) {
            /** @var User $user */
            $user = $this->getUser();
            $prefixes = $this->doctrine->getRepository(DocumentPrefix::class)->findAllForOrganisation($user->getOrganisation());
        } else {
            $prefixes = $this->doctrine->getRepository(DocumentPrefix::class)->findAll();
        }

        return $this->render('admin/prefix/index.html.twig', [
            'prefixes' => $prefixes,
        ]);
    }

    #[Route('/balie/prefix/new', name: 'app_admin_prefix_create', methods: ['GET', 'POST'])]
    #[AuthMatrix('prefix.create')]
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
            // If the user has organisation only filter, we need to force set the organisation in case the user somehow
            // managed to change it in the form.
            if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY) == true) {
                /** @var User $user */
                $user = $this->getUser();
                $prefix->setOrganisation($user->getOrganisation());
            }

            $this->doctrine->persist($prefix);
            $this->doctrine->flush();

            $this->addFlash('backend', ['success' => $this->translator->trans('Prefix created')]);

            return $this->redirectToRoute('app_admin_prefixes');
        }

        return $this->render('admin/prefix/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/prefixes/{id}', name: 'app_admin_prefix_edit', methods: ['GET', 'POST'])]
    #[AuthMatrix('prefix.update')]
    public function editPrefix(Breadcrumbs $breadcrumbs, Request $request, DocumentPrefix $prefix): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Prefix management', 'app_admin_prefixes');
        $breadcrumbs->addItem('Edit prefix');

        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            if ($prefix->getOrganisation() !== $currentUser->getOrganisation()) {
                $this->addFlash('backend', ['warning' => $this->translator->trans('Modifying this prefix is not allowed')]);

                return $this->redirectToRoute('app_admin_prefixes');
            }
        }

        $form = $this->createForm(DocumentPrefixType::class, $prefix);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->flush();
            $this->addFlash('backend', ['success' => $this->translator->trans('Prefix modified')]);

            return $this->redirectToRoute('app_admin_prefixes');
        }

        return $this->render('admin/prefix/edit.html.twig', [
            'prefix' => $prefix,
            'form' => $form->createView(),
        ]);
    }
}
