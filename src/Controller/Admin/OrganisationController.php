<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Organisation;
use App\Form\Organisation\OrganisationFormType;
use App\Service\OrganisationService;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    #[IsGranted('AuthMatrix.organisation.read')]
    public function index(): Response
    {
        $organisations = $this->doctrine->getRepository(Organisation::class)->findAll();

        return $this->render('admin/organisation/index.html.twig', [
            'organisations' => $organisations,
        ]);
    }

    #[Route('/balie/organisatie/new', name: 'app_admin_user_organisation_create', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.organisation.create')]
    public function create(Request $request): Response
    {
        $organisationForm = $this->createForm(OrganisationFormType::class);
        $organisationForm->handleRequest($request);

        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            /** @var Organisation $organisation */
            $organisation = $organisationForm->getData();

            // Use the service instead of the repo directly as it will do more work like audit logging
            $this->organisationService->create($organisation);

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/create.html.twig', [
            'organisationForm' => $organisationForm->createView(),
        ]);
    }

    #[Route('/balie/organisatie/{id}', name: 'app_admin_user_organisation_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.organisation.update')]
    public function modify(Request $request, Organisation $organisation): Response
    {
        $organisationForm = $this->createForm(OrganisationFormType::class, $organisation);
        $organisationForm->handleRequest($request);
        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            /** @var Organisation $organisation */
            $organisation = $organisationForm->getData();

            // Use the service instead of the repo directly as it will do more work like audit logging
            $this->organisationService->update($organisation);

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/edit.html.twig', [
            'organisation' => $organisation,
            'organisationForm' => $organisationForm->createView(),
        ]);
    }
}
