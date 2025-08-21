<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Organisation\Organisation;
use App\Domain\Organisation\OrganisationRepository;
use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Form\Organisation\OrganisationFormType;
use App\Service\OrganisationService;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class OrganisationController extends AbstractController
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly OrganisationService $organisationService,
    ) {
    }

    #[Route('/balie/organisatie', name: 'app_admin_user_organisation', methods: ['GET'])]
    #[IsGranted('AuthMatrix.organisation.read')]
    public function index(): Response
    {
        $organisations = $this->organisationRepository->getAllSortedByName();

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
            'prefixValues' => $this->getPrefixValues($organisationForm),
            'prefixErrors' => $this->getPrefixErrors($organisationForm),
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
            'prefixValues' => $this->getPrefixValues($organisationForm),
            'prefixErrors' => $this->getPrefixErrors($organisationForm),
        ]);
    }

    /**
     * @return array<array-key, array{value:Uuid, label:string}>
     */
    private function getDepartmentsOptions(): array
    {
        $departments = $this->departmentRepository->findAllSortedByName();

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

    /**
     * @return array<array-key, string>
     */
    private function getPrefixValues(FormInterface $form): array
    {
        $prefixes = $form->get('documentPrefixes')->getData();
        if (! $prefixes instanceof Collection) {
            return [];
        }

        return $prefixes->map(
            static function ($prefix): string {
                Assert::isInstanceOf($prefix, DocumentPrefix::class);

                return $prefix->getPrefix();
            },
        )->toArray();
    }

    /**
     * @return string[]
     */
    private function getPrefixErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->get('documentPrefixes')->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
