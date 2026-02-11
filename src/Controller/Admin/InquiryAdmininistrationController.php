<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use OutOfBoundsException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Form\Inquiry\AdministrationActionsType;
use Shared\Service\Inquiry\InquiryService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class InquiryAdmininistrationController extends AbstractController
{
    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly InquiryService $inquiryService,
    ) {
    }

    #[Route('/balie/admin/inquiry', name: 'app_admin_inquiry_administration', methods: ['GET'])]
    #[IsGranted('AuthMatrix.inquiry.administration')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('global.inquiry');

        return $this->render('admin/dossier/woo-decision/inquiry/administration/index.html.twig', [
            'inquiries' => $this->repository->findAll(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/balie/admin/inquiry/{casenr}', name: 'app_admin_inquiry_administration_details', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.inquiry.administration')]
    public function inquiry(
        #[MapEntity(mapping: ['casenr' => 'casenr'])] Inquiry $inquiry,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $form = $this->createForm(AdministrationActionsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            match ($form->get('action')->getData()) {
                AdministrationActionsType::ACTION_REGENERATE_INVENTORY => $this->inquiryService->generateInventory($inquiry),
                default => throw new OutOfBoundsException('Unknown inquiry administration action'),
            };

            $this->addFlash('backend', ['success' => 'The action has been scheduled for execution']);
        }

        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('global.inquiry', 'app_admin_inquiry_administration');
        $breadcrumbs->addItem($inquiry->getCasenr());

        return $this->render('admin/dossier/woo-decision/inquiry/administration/details.html.twig', [
            'inquiry' => $inquiry,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
