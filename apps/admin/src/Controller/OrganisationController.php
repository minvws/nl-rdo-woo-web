<?php

declare(strict_types=1);

namespace Admin\Controller;

use Admin\Form\Organisation\OrganisationFormData;
use Admin\Form\Organisation\OrganisationFormMapper;
use Admin\Form\Organisation\OrganisationFormType;
use Doctrine\Common\Collections\Collection;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Service\OrganisationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function array_map;
use function is_array;

class OrganisationController extends AbstractController
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly OrganisationFormMapper $organisationFormMapper,
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
        $organisationForm = $this->createForm(OrganisationFormType::class, new OrganisationFormData());
        $organisationForm->handleRequest($request);

        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            $data = $organisationForm->getData();
            Assert::isInstanceOf($data, OrganisationFormData::class);

            // Use the service instead of the repo directly as it will do more work like audit logging
            $this->organisationService->create($this->organisationFormMapper->create($data));

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/create.html.twig', [
            'organisationForm' => $organisationForm,
            'departmentOptions' => $this->getDepartmentsOptions(),
            'departmentValues' => $this->getDepartmentsValues($organisationForm),
            'departmentsErrors' => $this->getDepartmentsErrors($organisationForm),
        ]);
    }

    #[Route('/balie/organisatie/{id}', name: 'app_admin_user_organisation_edit', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.organisation.update')]
    public function modify(Request $request, Organisation $organisation): Response
    {
        $data = OrganisationFormData::fromEntity($organisation);
        $organisationForm = $this->createForm(
            OrganisationFormType::class,
            $data,
            ['prefix_editable' => false],
        );
        $organisationForm->handleRequest($request);
        if ($organisationForm->isSubmitted() && $organisationForm->isValid()) {
            $data = $organisationForm->getData();
            Assert::isInstanceOf($data, OrganisationFormData::class);
            $this->organisationFormMapper->apply($data, $organisation);

            // Use the service instead of the repo directly as it will do more work like audit logging
            $this->organisationService->update($organisation);

            return new RedirectResponse($this->generateUrl('app_admin_user_organisation', []));
        }

        return $this->render('admin/organisation/edit.html.twig', [
            'organisation' => $organisation,
            'organisationForm' => $organisationForm,
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
        if ($departments instanceof Collection) {
            $departments = $departments->toArray();
        }

        if (! is_array($departments)) {
            return [];
        }

        return array_map(
            static function ($department): Uuid {
                Assert::isInstanceOf($department, Department::class);

                return $department->getId();
            },
            $departments,
        );
    }

    /**
     * @return array<array-key, string>
     */
    private function getDepartmentsErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->get('departments')->getErrors(true) as $error) {
            Assert::isInstanceOf($error, FormError::class);

            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
