<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\User\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected UserPasswordHasherInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $doctrine, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->doctrine = $doctrine;
        $this->passwordEncoder = $passwordEncoder;
    }

    #[Route(path: '/balie/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/balie/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/balie/change-password', name: 'app_change_password')]
    public function changePassword(Request $request): Response
    {
        $form = $this->createForm(ChangePasswordType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            // Encode the new password
            $newpassword = strval($form->get('plainPassword')->getData());
            $hash = $this->passwordEncoder->hashPassword($user, $newpassword);
            $user->setPassword($hash);
            $user->setChangepwd(false);

            $this->doctrine->flush();

            // Redirect
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
