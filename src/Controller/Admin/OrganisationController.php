<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\Organisation;
use App\Form\Organisation\OrganisationFormType;
use App\Service\OrganisationService;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

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
        AuthorizationMatrix $authorizationMatrix,
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
            'departmentOptions' => $this->getDepartmentsOptions(),
            'departmentValues' => $this->getDepartmentsValues($organisationForm),
            'departmentsErrors' => $this->getDepartmentsErrors($organisationForm),
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
            'departmentOptions' => $this->getDepartmentsOptions(),
            'departmentValues' => $this->getDepartmentsValues($organisationForm),
            'departmentsErrors' => $this->getDepartmentsErrors($organisationForm),
        ]);
    }

    /**
     * @return array<array-key, array{value:Uuid, label:string}>
     */
    private function getDepartmentsOptions(): array
    {
        $departments = $this->doctrine->getRepository(Department::class)->findAll();

        return array_map(
            static fn (Department $department) => [
                'value' => $department->getId(),
                'label' => $department->getName(),
            ],
            $departments,
        );
    }

    /**
     * @return array<array-key, Uuid>
     */
    private function getDepartmentsValues(FormInterface $form): array
    {
        $departments = $form->get('departments')->getData();
        if (! $departments instanceof Collection) {
            return [];
        }

        return $departments->map(
            static function ($department): Uuid {
                Assert::isInstanceOf($department, Department::class);

                return $department->getId();
            },
        )->toArray();
    }

    /**
     * @return string[]
     */
    private function getDepartmentsErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->get('departments')->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
