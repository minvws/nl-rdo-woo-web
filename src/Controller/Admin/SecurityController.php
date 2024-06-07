<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\User\ChangePasswordType;
use App\Roles;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected UserPasswordHasherInterface $passwordEncoder;

    public function __construct(
        EntityManagerInterface $doctrine,
        UserPasswordHasherInterface $passwordEncoder,
        private readonly TranslatorInterface $translator
    ) {
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

    #[Route(path: '/balie/profiel', name: 'app_admin_user_profile')]
    public function viewLoggedInUser(Request $request): Response
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

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.user.password_changed')]);

            // Redirect to target path if exists
            if ($request->getSession()->has('target_path')) {
                $targetPath = strval($request->getSession()->get('target_path'));
                $request->getSession()->remove('target_path');

                return $this->redirect($targetPath);
            }

            return $this->redirectToRoute('app_admin_user_profile');
        }

        /** @var LoggableUser $loggedInUser */
        $loggedInUser = $this->getUser();

        $roles = Roles::roleDetails();
        $roleDetails = [];
        foreach ($roles as $role) {
            $roleDetails[$role['role']] = $role['description'];
        }

        return $this->render('security/profile.html.twig', [
            'form' => $form->createView(),
            'hasFormErrors' => count($form->getErrors(true)) > 0,
            'user' => $loggedInUser,
            'role_details' => $roleDetails,
        ]);
    }
}
