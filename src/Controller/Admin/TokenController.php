<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\Token;
use App\Form\TokenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class TokenController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    #[Route('/balie/tokens', name: 'app_admin_tokens', methods: ['GET'])]
    #[AuthMatrix('token.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Token management');

        $tokens = $this->doctrine->getRepository(Token::class)->findAll();

        return $this->render('admin/token/index.html.twig', [
            'tokens' => $tokens,
        ]);
    }

    #[Route('/balie/token/new', name: 'app_admin_token_new', methods: ['GET', 'POST'])]
    #[AuthMatrix('token.create')]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Token management', 'app_admin_tokens');
        $breadcrumbs->addItem('New token');

        $token = new Token();
        $form = $this->createForm(TokenType::class, $token);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $token->setExpiryDate(new \DateTimeImmutable('+1 week'));
            $this->doctrine->persist($token);
            $this->doctrine->flush();

            $this->addFlash('backend', ['success' => $this->translator->trans('Token created')]);

            return $this->redirectToRoute('app_admin_tokens');
        }

        return $this->render('admin/token/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/token/{id}', name: 'app_admin_token_edit', methods: ['GET', 'POST'])]
    #[AuthMatrix('token.update')]
    public function modify(Breadcrumbs $breadcrumbs, Request $request, Token $token): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Token management', 'app_admin_tokens');
        $breadcrumbs->addItem('Edit token');

        $form = $this->createForm(TokenType::class, $token);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->flush();
            $this->addFlash('backend', ['success' => $this->translator->trans('Token modified')]);

            return $this->redirectToRoute('app_admin_tokens');
        }

        return $this->render('admin/token/edit.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
