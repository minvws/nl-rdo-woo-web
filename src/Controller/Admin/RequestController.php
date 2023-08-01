<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Inquiry;
use App\Form\Inquiry\InquiryEditType;
use App\Form\Inquiry\InquiryType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class RequestController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected UserService $userService;
    protected PaginatorInterface $paginator;

    public function __construct(EntityManagerInterface $doctrine, UserService $userService, PaginatorInterface $paginator)
    {
        $this->doctrine = $doctrine;
        $this->userService = $userService;
        $this->paginator = $paginator;
    }

    #[Route('/balie/requests', name: 'app_admin_requests', methods: ['GET'])]
    public function index(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Request management');

        $query = $this->doctrine->getRepository(Inquiry::class)->createQueryBuilder('r')->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/request/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/balie/requests/new', name: 'app_admin_request_new', methods: ['GET', 'POST'])]
    public function new(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Request management', 'app_admin_requests');
        $breadcrumbs->addItem('New request');

        $inquiry = new Inquiry();
        $form = $this->createForm(InquiryType::class, $inquiry);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $caseNr = random_int(10, 99) . '-' . random_int(100, 999);
            $inquiry->setCasenr($caseNr);
            $inquiry->setCreatedAt(new \DateTimeImmutable());
            $inquiry->setUpdatedAt(new \DateTimeImmutable());

            $this->doctrine->persist($inquiry);
            $this->doctrine->flush();

            return $this->redirectToRoute('app_admin_requests');
        }

        return $this->render('admin/request/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/requests/{id}', name: 'app_admin_request_edit', methods: ['GET', 'POST'])]
    public function edit(Breadcrumbs $breadcrumbs, Request $request, Inquiry $inquiry): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Request management', 'app_admin_requests');
        $breadcrumbs->addItem('Edit request');

        $form = $this->createForm(InquiryEditType::class, $inquiry);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inquiry->setUpdatedAt(new \DateTimeImmutable());

            $this->doctrine->persist($inquiry);
            $this->doctrine->flush();

            return $this->redirectToRoute('app_admin_requests');
        }

        return $this->render('admin/request/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
