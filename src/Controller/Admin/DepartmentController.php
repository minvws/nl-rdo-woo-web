<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\GovernmentOfficial;
use App\Form\DepartmentType;
use App\Form\GovernmentOfficialType;
use App\Message\UpdateDepartmentMessage;
use App\Message\UpdateOfficialMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DepartmentController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected MessageBusInterface $messageBus;
    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $doctrine,
        MessageBusInterface $messageBus,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->messageBus = $messageBus;
        $this->translator = $translator;
    }

    #[Route('/balie/departementen', name: 'app_admin_departments', methods: ['GET'])]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Department management');

        $departments = $this->doctrine->getRepository(Department::class)->findAll();
        $governmentOfficials = $this->doctrine->getRepository(GovernmentOfficial::class)->findAll();

        return $this->render('admin/departments/index.html.twig', [
            'departments' => $departments,
            'heads' => $governmentOfficials,
        ]);
    }

    #[Route('/balie/departmenten/new', name: 'app_admin_department_create', methods: ['GET', 'POST'])]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('New department');

        $department = new Department();
        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->persist($department);
            $this->doctrine->flush();

            $this->addFlash('success', $this->translator->trans('Department created'));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/bewindsvoerders/new', name: 'app_admin_governmentofficial_create', methods: ['GET', 'POST'])]
    public function createHead(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('New official');

        $governmentOfficial = new GovernmentOfficial();
        $form = $this->createForm(GovernmentOfficialType::class, $governmentOfficial);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->persist($governmentOfficial);
            $this->doctrine->flush();

            $this->addFlash('success', $this->translator->trans('Official created'));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/create-head.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/bewindsvoerders/{id}', name: 'app_admin_governmentofficial_edit', methods: ['GET', 'POST'])]
    public function modifyHead(Breadcrumbs $breadcrumbs, Request $request, GovernmentOfficial $head): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('Edit official');

        $form = $this->createForm(GovernmentOfficialType::class, $head);

        $oldHead = clone $head;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->flush();
            $this->addFlash('success', $this->translator->trans('Official modified'));

            $this->messageBus->dispatch(new UpdateOfficialMessage($oldHead, $head));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/edit-head.html.twig', [
            'head' => $head,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/departmenten/{id}', name: 'app_admin_department_edit', methods: ['GET', 'POST'])]
    public function modify(Breadcrumbs $breadcrumbs, Request $request, Department $department): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Department management', 'app_admin_departments');
        $breadcrumbs->addItem('Edit department');

        $form = $this->createForm(DepartmentType::class, $department);

        $oldDepartment = clone $department;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->flush();
            $this->addFlash('success', $this->translator->trans('Department modified'));

            $this->messageBus->dispatch(new UpdateDepartmentMessage($oldDepartment, $department));

            return $this->redirectToRoute('app_admin_departments');
        }

        return $this->render('admin/departments/edit.html.twig', [
            'department' => $department,
            'form' => $form->createView(),
        ]);
    }
}
