<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Dossier\DossierAuthorizationTrait;
use App\Entity\Inquiry;
use App\Form\Inquiry\AdministrationActionsType;
use App\Repository\InquiryRepository;
use App\Service\Inquiry\InquiryService;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class InquiryAdmininistrationController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly InquiryService $inquiryService,
    ) {
    }

    #[Route('/balie/admin/inquiry', name: 'app_admin_inquiry_administration', methods: ['GET'])]
    #[IsGranted('AuthMatrix.inquiry.administration')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Administration', 'app_admin');
        $breadcrumbs->addItem('Inquiry');

        return $this->render('admin/inquiry/administration/index.html.twig', [
            'inquiries' => $this->repository->findAll(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/balie/admin/inquiry/{casenr}', name: 'app_admin_inquiry_administration_details', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.inquiry.administration')]
    public function inquiry(
        Inquiry $inquiry,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $form = $this->createForm(AdministrationActionsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            switch ($form->get('action')->getData()) {
                case AdministrationActionsType::ACTION_REGENERATE_INVENTORY:
                    $this->inquiryService->generateInventory($inquiry);
                    break;
                case AdministrationActionsType::ACTION_REGENERATE_ARCHIVES:
                    $this->inquiryService->generateArchives($inquiry);
                    break;
                default:
                    throw new \OutOfBoundsException('Unknown inquiry administration action');
            }

            $this->addFlash('backend', ['success' => 'The action has been scheduled for execution']);
        }

        $breadcrumbs->addRouteItem('Administration', 'app_admin');
        $breadcrumbs->addRouteItem('Inquiry', 'app_admin_inquiry_administration');
        $breadcrumbs->addItem($inquiry->getCasenr());

        return $this->render('admin/inquiry/administration/details.html.twig', [
            'inquiry' => $inquiry,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
