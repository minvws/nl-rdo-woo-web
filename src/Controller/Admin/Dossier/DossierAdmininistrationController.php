<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Attribute\AuthMatrix;
use App\Entity\Dossier;
use App\Form\Dossier\AdministrationActionsType;
use App\Repository\DossierRepository;
use App\Service\DossierService;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DossierAdmininistrationController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly DossierRepository $repository,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly DossierService $dossierService,
    ) {
    }

    #[Route('/balie/admin/dossiers', name: 'app_admin_dossier_administration', methods: ['GET'])]
    #[AuthMatrix('dossier.administration')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Administration', 'app_admin');
        $breadcrumbs->addItem('Dossier');

        return $this->render('admin/dossier/administration/index.html.twig', [
            'dossiers' => $this->repository->findAll(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/balie/admin/dossiers/{dossierId}', name: 'app_admin_dossier_administration_details', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.administration')]
    public function dossier(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $form = $this->createForm(AdministrationActionsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            switch ($form->get('action')->getData()) {
                case AdministrationActionsType::ACTION_INGEST:
                    $this->dossierService->ingest($dossier);
                    break;
                case AdministrationActionsType::ACTION_UPDATE:
                    $this->dossierService->update($dossier);
                    break;
                case AdministrationActionsType::ACTION_REGENERATE_ARCHIVES:
                    $this->dossierService->generateArchives($dossier);
                    break;
                case AdministrationActionsType::ACTION_REGENERATE_CLEAN_INVENTORY:
                    $this->dossierService->generateSanitizedInventory($dossier);
                    break;
                default:
                    throw new \OutOfBoundsException('Unknown dossier administration action');
            }

            $this->addFlash('backend', ['success' => 'The action has been scheduled for execution']);
        }

        $breadcrumbs->addRouteItem('Administration', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier', 'app_admin_dossier_administration');
        $breadcrumbs->addItem('Dossier ' . $dossier->getDossierNr());

        return $this->render('admin/dossier/administration/details.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
