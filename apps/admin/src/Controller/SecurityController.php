<?php

declare(strict_types=1);

namespace Admin\Controller;

use Admin\Form\User\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

use function count;
use function is_string;

class SecurityController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $doctrine,
        protected UserPasswordHasherInterface $passwordEncoder,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/balie/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/balie/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/balie/profiel', name: 'app_admin_user_profile')]
    public function viewLoggedInUser(Request $request): Response
    {
        $form = $this->createForm(ChangePasswordType::class);

        $user = $this->getUser();
        Assert::isInstanceOf($user, User::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            Assert::string($plainPassword);
            $hash = $this->passwordEncoder->hashPassword($user, $plainPassword);

            $user->setPassword($hash);
            $user->setChangepwd(false);

            $this->doctrine->flush();

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.user.password_changed')]);

            $targetPath = $request->getSession()->remove('target_path');
            if (is_string($targetPath)) {
                return $this->redirect($targetPath);
            }

            return $this->redirectToRoute('app_admin_user_profile');
        }

        return $this->render('admin/security/profile.html.twig', [
            'form' => $form,
            'hasFormErrors' => count($form->getErrors(true)) > 0,
            'user' => $user,
            'role_descriptions' => Roles::roleDescriptions(),
        ]);
    }
}
