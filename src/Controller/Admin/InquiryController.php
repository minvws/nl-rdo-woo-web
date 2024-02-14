<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Dossier\DossierAuthorizationTrait;
use App\Entity\DocumentPrefix;
use App\Entity\Dossier;
use App\Form\ChoiceLoader\DocumentPrefixChoiceLoader;
use App\Form\ChoiceLoader\DossierChoiceLoader;
use App\Form\Dossier\TranslatableFormErrorMapper;
use App\Form\Inquiry\InquiryLinkDocumentsFormType;
use App\Form\Inquiry\InquiryLinkDossierFormType;
use App\Repository\InquiryRepository;
use App\Service\Inquiry\InquiryLinkImporter;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\InquiryChangeset;
use App\Service\Inventory\InventoryDataHelper;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InquiryController extends AbstractController
{
    use DossierAuthorizationTrait;

    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly PaginatorInterface $paginator,
        private readonly EntityManagerInterface $doctrine,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly Security $security,
        private readonly InquiryService $inquiryService,
        private readonly InquiryLinkImporter $inquiryImporter,
        private readonly TranslatableFormErrorMapper $formErrorMapper,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/verzoeken', name: 'app_admin_inquiries', methods: ['GET'])]
    #[IsGranted('AuthMatrix.inquiry.read')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            $this->repository->getQueryWithDocCountAndDossierCount($this->authorizationMatrix->getActiveOrganisation()),
            $request->query->getInt('page', 1),
            self::MAX_ITEMS_PER_PAGE
        );

        return $this->render('admin/inquiry/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/balie/verzoeken/link', name: 'app_admin_inquiries_link', methods: ['GET'])]
    #[IsGranted('AuthMatrix.inquiry.create')]
    public function link(): Response
    {
        return $this->render('admin/inquiry/link.html.twig', [
            'placeholder' => '',
        ]);
    }

    #[Route('/balie/verzoeken/link/documenten', name: 'app_admin_inquiries_link_documents', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.inquiry.create')]
    public function linkDocuments(Request $request): Response
    {
        $choiceLoader = new DocumentPrefixChoiceLoader($this->doctrine, $this->authorizationMatrix, $this->security);
        $form = $this->createForm(InquiryLinkDocumentsFormType::class, null, ['choice_loader' => $choiceLoader]);

        $form->handleRequest($request);

        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_inquiries');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('upload')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                /** @var DocumentPrefix $prefix */
                $prefix = $form->get('prefix')->getData();
                $errors = $this->inquiryImporter->processSpreadsheet(
                    $this->authorizationMatrix->getActiveOrganisation(),
                    $uploadedFile,
                    $prefix
                );

                if (count($errors['generic']) === 0 && count($errors['row']) === 0) {
                    $this->addFlash('backend', ['success' => $this->translator->trans('Case numbers are being been linked')]);

                    return $this->redirectToRoute('app_admin_inquiries');
                }

                $this->formErrorMapper->mapGenericErrorsToForm($errors['generic'], $form);

                $this->formErrorMapper->mapRowErrorsToForm($errors['row'], $form);
            }
        }

        return $this->render('admin/inquiry/link_documents.html.twig', [
            'placeholder' => '',
            'link_documents' => $form->createView(),
        ]);
    }

    #[Route('/balie/verzoeken/link/besluiten', name: 'app_admin_inquiries_link_dossiers', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.inquiry.create')]
    public function linkDossiers(Request $request): Response
    {
        $choiceLoader = new DossierChoiceLoader($this->doctrine, $this->authorizationMatrix, $this->security);
        $form = $this->createForm(InquiryLinkDossierFormType::class, null, ['choice_loader' => $choiceLoader]);

        $form->handleRequest($request);

        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_inquiries');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $inquiryChangeset = new InquiryChangeset($this->authorizationMatrix->getActiveOrganisation());
            $caseNrs = InventoryDataHelper::separateValues(strval($form->get('map')->getData()), ',');

            /** @var Dossier[] $dossiers */
            $dossiers = $form->get('dossiers')->getData();
            foreach ($dossiers as $dossier) {
                $this->testIfDossierIsAllowedByUser($dossier);
                $inquiryChangeset->addCaseNrsForDossier($dossier, $caseNrs);
            }

            $this->inquiryService->applyChangesetAsync($inquiryChangeset);

            return $this->redirectToRoute('app_admin_inquiries');
        }

        return $this->render('admin/inquiry/link_dossiers.html.twig', [
            'placeholder' => '',
            'inquiry_link_form' => $form->createView(),
        ]);
    }
}
