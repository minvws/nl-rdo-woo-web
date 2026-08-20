<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier;

use Knp\Component\Pager\PaginatorInterface;
use Shared\ApplicationId;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use Shared\Domain\Publication\Dossier\Admin\DossierListingService;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\Dossier\ViewModel\DossierTypeViewFactory;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Form\Dossier\SearchFormType;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;

use function count;
use function reset;

class DossierController extends AbstractController
{
    protected const int MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly DossierListingService $listingService,
        private readonly PaginatorInterface $paginator,
        private readonly WizardStatusFactory $wizardStatusFactory,
        private readonly DossierTypeManager $dossierTypeManager,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly DossierPathHelper $dossierPathHelper,
        private readonly DossierTypeViewFactory $dossierTypeViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly NoticeNotPublicRepository $noticeNotPublicRepository,
    ) {
    }

    #[Route('/balie/dossiers', name: 'app_admin_dossiers', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $filterParameters = $form->getData();
        Assert::nullOrIsInstanceOf($filterParameters, DossierFilterParameters::class);

        $query = $this->listingService->getFilteredListingQuery($filterParameters);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            self::MAX_ITEMS_PER_PAGE,
            [
                'defaultSortFieldName' => 'dos.createdAt',
                'defaultSortDirection' => 'desc',
            ],
        );

        return $this->render('admin/dossier/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'formData' => $form->getData(),
        ]);
    }

    #[Route('/balie/dossier/overview/{documentPrefix}/{dossierNumber}', name: 'app_admin_dossier', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function dossier(
        #[MapEntity(
            class: AbstractDossier::class,
            mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'],
        )]
        AbstractDossier $dossier,
    ): Response {
        $mainDocumentView = null;
        if ($dossier instanceof EntityWithMainDocument && $dossier->getMainDocument() instanceof AbstractMainDocument) {
            $mainDocumentView = $this->mainDocumentViewFactory->make(
                $dossier,
                $dossier->getMainDocument(),
                ApplicationId::ADMIN,
            );
        }

        $noticeNotPublic = $this->noticeNotPublicRepository->findOneByDossierId($dossier->getId());

        return $this->render(
            'admin/dossier/' . $dossier->getType()->value . '/view.html.twig',
            [
                'attachments' => $this->attachmentViewFactory->makeCollection(
                    $dossier,
                    ApplicationId::ADMIN,
                ),
                'mainDocument' => $mainDocumentView,
                'noticeNotPublic' => $noticeNotPublic,
                'dossier' => $dossier,
                'workflowStatus' => $this->wizardStatusFactory->getWizardStatus($dossier),
                'publicDossierUrl' => $this->dossierPathHelper->getAbsoluteDetailsPath($dossier),
            ],
        );
    }

    #[Route(
        path: '/balie/dossier/overview/{documentPrefix}/{dossierNumber}/publication-confirmation',
        name: 'app_admin_dossier_publication_confirmation',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function publicationConfirmation(
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] AbstractDossier $dossier,
    ): Response {
        return $this->render('admin/dossier/' . $dossier->getType()->value . '/publication-confirmation.html.twig', [
            'dossier' => $dossier,
            'publicDossierUrl' => $this->dossierPathHelper->getAbsoluteDetailsPath($dossier),
        ]);
    }

    #[Route('/balie/dossier/create', name: 'app_admin_dossier_create', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.create')]
    public function create(): Response
    {
        $typeConfigs = $this->dossierTypeManager->getAvailableConfigs();
        if (count($typeConfigs) === 1) {
            $typeConfig = reset($typeConfigs);

            return $this->redirectToRoute($typeConfig->getCreateRouteName());
        }

        return $this->render('admin/dossier/create.html.twig', [
            'dossierTypes' => $this->dossierTypeViewFactory->makeCollection($typeConfigs),
        ]);
    }
}
