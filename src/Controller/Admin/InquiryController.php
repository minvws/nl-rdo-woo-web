<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Form\ChoiceLoader\DocumentPrefixChoiceLoader;
use Shared\Form\ChoiceLoader\WooDecisionChoiceLoader;
use Shared\Form\Inquiry\InquiryLinkDocumentsFormType;
use Shared\Form\Inquiry\InquiryLinkDossierFormType;
use Shared\Service\Inquiry\CaseNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkImporter;
use Shared\Service\Inquiry\InquiryService;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class InquiryController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly PaginatorInterface $paginator,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly EntityManagerInterface $doctrine,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly Security $security,
        private readonly InquiryService $inquiryService,
        private readonly InquiryLinkImporter $inquiryImporter,
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

        return $this->render('admin/dossier/woo-decision/inquiry/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/balie/verzoeken/link', name: 'app_admin_inquiries_link', methods: ['GET'])]
    #[IsGranted('AuthMatrix.inquiry.create')]
    public function link(): Response
    {
        return $this->render('admin/dossier/woo-decision/inquiry/link.html.twig', [
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

        $result = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('upload')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                /** @var DocumentPrefix $prefix */
                $prefix = $form->get('prefix')->getData();
                $result = $this->inquiryImporter->import(
                    $this->authorizationMatrix->getActiveOrganisation(),
                    $uploadedFile,
                    $prefix
                );
            }
        }

        return $this->render('admin/dossier/woo-decision/inquiry/link_documents.html.twig', [
            'placeholder' => '',
            'link_documents' => $form->createView(),
            'result' => $result,
        ]);
    }

    #[Route('/balie/verzoeken/link/besluiten', name: 'app_admin_inquiries_link_dossiers', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.inquiry.create')]
    public function linkDossiers(Request $request): Response
    {
        $choiceLoader = new WooDecisionChoiceLoader($this->wooDecisionRepository, $this->authorizationMatrix, $this->security);
        $form = $this->createForm(InquiryLinkDossierFormType::class, null, ['choice_loader' => $choiceLoader]);

        $form->handleRequest($request);

        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_inquiries');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $inquiryChangeset = new InquiryChangeset($this->authorizationMatrix->getActiveOrganisation());
            $caseNrs = CaseNumbers::fromCommaSeparatedString(
                strval($form->get('map')->getData()),
            );

            /** @var WooDecision[] $dossiers */
            $dossiers = $form->get('dossiers')->getData();
            foreach ($dossiers as $dossier) {
                $this->denyAccessUnlessGranted('AuthMatrix.dossier.read', subject: $dossier);
                $inquiryChangeset->addCaseNrsForDossier($dossier, $caseNrs);
            }

            $this->inquiryService->applyChangesetAsync($inquiryChangeset);

            return $this->redirectToRoute('app_admin_inquiries');
        }

        return $this->render('admin/dossier/woo-decision/inquiry/link_dossiers.html.twig', [
            'placeholder' => '',
            'inquiry_link_form' => $form->createView(),
        ]);
    }
}
