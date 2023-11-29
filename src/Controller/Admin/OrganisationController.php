<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\Organisation;
use App\Entity\User;
use App\Form\Organisation\OrganisationCreateFormType;
use App\Form\Organisation\OrganisationUpdateFormType;
use App\Service\OrganisationService;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrganisationController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected OrganisationService $organisationService;
    protected TranslatorInterface $translator;
    protected AuditLogger $auditLogger;
    protected AuthorizationMatrix $authorizationMatrix;

    public function __construct(
        EntityManagerInterface $doctrine,
        OrganisationService $organisationService,
        TranslatorInterface $translator,
        AuditLogger $auditLogger,
        AuthorizationMatrix $authorizationMatrix
    ) {
        $this->doctrine = $doctrine;
        $this->organisationService = $organisationService;
        $this->translator = $translator;
        $this->auditLogger = $auditLogger;
        $this->authorizationMatrix = $authorizationMatrix;
    }

    #[Route('/balie/organisatie', name: 'app_admin_user_organisation', methods: ['GET'])]
    #[AuthMatrix('organisation.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Organisation management');

        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)) {
            /** @var User $user */
            $user = $this->getUser();
            $organisations = $this->doctrine->getRepository(Organisation::class)->findBy(['id' => $user->getOrganisation()->getId()]);
        } else {
            $organisations = $this->doctrine->getRepository(Organisation::class)->findAll();
        }

        return $this->render('admin/organisation/index.html.twig', [
            'organisations' => $organisations,
        ]);
    }

    #[Route('/balie/organisatie/new', name: 'app_admin_user_organisation_create', methods: ['GET', 'POST'])]
    #[AuthMatrix('organisation.create')]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('User management', 'app_admin_users');
        $breadcrumbs->addRouteItem('Organisation management', 'app_admin_user_organisation');
        $breadcrumbs->addItem('New organisation');

        $organisationForm = $this->createForm(OrganisationCreateFormType::class);
        $organisationForm->handleRequest($request);
        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            /** @var Organisation $organisation */
            $organisation = $organisationForm->getData();

            // This seems double work as we already got an organisation object from the form
            // but it will do more work like audit logging etc.
            $this->organisationService->create(
                name: $organisation->getName(),
                department: $organisation->getDepartment()
            );

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/create.html.twig', [
            'organisationForm' => $organisationForm->createView(),
        ]);
    }

    #[Route('/balie/organisatie/{id}', name: 'app_admin_user_organisation_edit', methods: ['GET', 'POST'])]
    #[AuthMatrix('organisation.update')]
    public function modify(Breadcrumbs $breadcrumbs, Request $request, Organisation $organisation): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('User management', 'app_admin_users');
        $breadcrumbs->addRouteItem('Organisation management', 'app_admin_user_organisation');
        $breadcrumbs->addItem('Edit organisation');

        $organisationForm = $this->createForm(OrganisationUpdateFormType::class, $organisation);
        $organisationForm->handleRequest($request);
        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            /** @var Organisation $newOrganisationData */
            $newOrganisationData = $organisationForm->getData();

            // This seems double work as we already got an organisation object from the form
            // but it will do more work like audit logging etc.
            $this->organisationService->update(
                $organisation,
                name: $newOrganisationData->getName(),
                department: $newOrganisationData->getDepartment()
            );

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/edit.html.twig', [
            'organisation' => $organisation,
            'organisation_info' => $organisationForm->createView(),
        ]);
    }
}
