<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier\WooDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Dossier\DossierBreadcrumbFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Document\WithdrawDocumentFormType;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Shared\ValueObject\DocumentNumber;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class DocumentActionController extends AbstractController
{
    public const string ROUTE_NAME_DOSSIER_WOODECISION_DOCUMENT_WITHDRAW = 'app_admin_dossier_woodecision_document_withdraw';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DocumentDispatcher $dispatcher,
        private readonly DocumentRepository $documentRepository,
        private readonly DossierBreadcrumbFactory $breadcrumbFactory,
    ) {
    }

    #[Route(
        '/balie/dossier/woodecision/document/withdraw/{documentPrefix}/{dossierNumber}/{documentNumber}',
        self::ROUTE_NAME_DOSSIER_WOODECISION_DOCUMENT_WITHDRAW,
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function withdraw(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] WooDecision $dossier,
        DocumentNumber $documentNumber,
    ): Response {
        $document = $this->documentRepository->findOneByDossierNumberAndDocumentNumber(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNumber(),
            $documentNumber,
        );

        if ($document === null) {
            throw $this->createNotFoundException();
        }

        $breadcrumbs->addRouteItem(
            $this->breadcrumbFactory->forDossier($dossier),
            'app_admin_dossier',
            ['documentPrefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addRouteItem(
            'global.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['documentPrefix' => $dossier->getDocumentPrefix(), 'dossierNumber' => $dossier->getDossierNumber()],
        );
        $breadcrumbs->addRouteItem('global.document', 'app_admin_dossier_woodecision_document', [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'documentNumber' => $documentNumber->toString(),
        ]);
        $breadcrumbs->addItem('admin.dossiers.woo-decision.step.withdraw_document');

        $form = $this->createForm(WithdrawDocumentFormType::class);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        $cancelButton = $form->get('cancel');
        Assert::isInstanceOf($cancelButton, SubmitButton::class);

        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_dossier_woodecision_document', [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
                'documentNumber' => $documentNumber,
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->get('reason')->getData();
            Assert::isInstanceOf($reason, DocumentWithdrawReason::class);

            $explanation = $form->get('explanation')->getData();
            Assert::string($explanation);

            $this->dispatcher->dispatchWithDrawDocumentCommand($dossier, $document, $reason, $explanation);

            $this->addFlash(
                'backend',
                ['success' => $this->translator->trans('admin.dossiers.action.withdraw_document_executing')],
            );

            return $this->redirectToRoute(
                'app_admin_dossier_woodecision_document',
                [
                    'documentPrefix' => $dossier->getDocumentPrefix(),
                    'dossierNumber' => $dossier->getDossierNumber(),
                    'documentNumber' => $documentNumber->toString(),
                ],
            );
        }

        return $this->render('admin/dossier/woo-decision/document/withdraw.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'documentNumber' => $documentNumber,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
            'workflow' => new DocumentWorkflowStatus($document),
        ]);
    }
}
