<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier;

use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Form\Dossier\WithdrawAttachmentFormType;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class AttachmentActionController extends AbstractController
{
    public function __construct(
        private readonly AttachmentDispatcher $dispatcher,
        private readonly WizardStatusFactory $wizardStatusFactory,
    ) {
    }

    #[Route(
        path: '/balie/dossier/attachment/withdraw/{prefix}/{dossierId}/{attachmentId}',
        name: 'app_admin_dossier_attachment_withdraw',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function withdraw(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AbstractDossier $dossier,
        #[MapEntity(mapping: ['attachmentId' => 'id'])] AbstractAttachment $attachment,
    ): Response {
        if ($attachment->getDossier() !== $dossier) {
            throw $this->createNotFoundException('Attachment not found in dossier');
        }

        $wizardStatus = $this->wizardStatusFactory->getWizardStatus($dossier);

        $breadcrumbs->addRouteItem(
            $dossier->getDossierNr(),
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );

        Assert::notNull($attachment->getFileInfo()->getName());
        $breadcrumbs->addRouteItem(
            $attachment->getFileInfo()->getName(),
            $wizardStatus->getAttachmentStep()->getRouteName(),
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
        );
        $breadcrumbs->addItem('admin.dossiers.attachment.withdraw.title');

        $form = $this->createForm(WithdrawAttachmentFormType::class);
        $form->handleRequest($request);

        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute(
                $wizardStatus->getAttachmentStep()->getRouteName(),
                [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                ],
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AttachmentWithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->dispatcher->dispatchWithDrawAttachmentCommand($dossier, $attachment, $reason, $explanation);
        }

        return $this->render('admin/dossier/attachment/withdraw.html.twig', [
            'dossier' => $dossier,
            'attachment' => $attachment,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
        ]);
    }
}
