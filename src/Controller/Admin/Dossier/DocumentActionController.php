<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Attribute\AuthMatrix;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\User;
use App\Entity\WithdrawReason;
use App\Exception\DocumentReplaceException;
use App\Form\Document\ReplaceFormType;
use App\Form\Document\WithdrawFormType;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DocumentActionController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly DocumentWorkflow $workflow,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}/withdraw', name: 'app_admin_document_withdraw', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function withdraw(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(dossierId,documentId)')] Document $document,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->testIfDossierIsAllowedByUser($user, $dossier);

        $breadcrumbs->addRouteItem($dossier->getDossierNr(), 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('Documenten', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_document',
            ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );
        $breadcrumbs->addItem('Document intrekken');

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_document', [
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->workflow->withdraw($document, $reason, $explanation);
        }

        return $this->render('admin/dossier/document/withdraw.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}/replace', name: 'app_admin_document_replace', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function replace(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(dossierId,documentId)')] Document $document,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->testIfDossierIsAllowedByUser($user, $dossier);

        $status = $this->workflow->getStatus($document);
        if (! $status->canReplace()) {
            return $this->redirectToRoute('app_admin_document', [
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ]);
        }

        $breadcrumbs->addRouteItem($dossier->getDossierNr(), 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('Documenten', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_document',
            ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );
        $breadcrumbs->addItem('Document vervangen');

        $error = '';
        $replaced = false;

        $form = $this->createForm(ReplaceFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('document')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                try {
                    $this->workflow->replace($dossier, $document, $uploadedFile);
                    $replaced = true;
                } catch (DocumentReplaceException $exception) {
                    $error = $this->translator->trans(
                        $exception->getMessage(),
                        [
                            'filename' => $exception->getFilename(),
                            'documentnr' => $exception->getDocument()->getDocumentId(),
                        ]
                    );
                }
            }
        }

        return $this->render('admin/dossier/document/replace.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
            'workflow' => $status,
            'replaced' => $replaced,
            'error' => $error,
        ]);
    }
}
