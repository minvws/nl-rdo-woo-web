<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Shared\Domain\Organisation\Organisation;
use Shared\Form\User\DisableUserFormType;
use Shared\Form\User\EnableUserFormType;
use Shared\Form\User\ResetPasswordFormType;
use Shared\Form\User\ResetTwoFactorAuthFormType;
use Shared\Form\User\UserCreateFormType;
use Shared\Form\User\UserInfoFormType;
use Shared\Service\PaginatorFactory;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class UserController extends AbstractController
{
    private const string RESET_USER_KEY = 'reset_user';

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly PaginatorFactory $paginatorFactory,
        private readonly Security $security,
    ) {
    }

    #[Route('/balie/gebruikers', name: 'app_admin_users', methods: ['GET'])]
    #[IsGranted('AuthMatrix.user.read')]
    public function index(Request $request): Response
    {
        // Remove any reset data from the session as soon as we return back to the user list. This is not
        // 100% foolproof but it should work in most cases. In cases it doesn't, there is nothing wrong as
        // the data is never shown to the user.
        if ($request->getSession()->has(self::RESET_USER_KEY)) {
            $request->getSession()->remove(self::RESET_USER_KEY);
        }

        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        $activeUsersPaginator = $this->paginatorFactory->createForQuery(
            key: 'au',
            query: $this->repository->findActiveUsersForOrganisationQuery($organisation),
            defaultSortField: 'u.name',
        );

        $deactivatedUsersPaginator = $this->paginatorFactory->createForQuery(
            key: 'du',
            query: $this->repository->findDeactivatedUsersForOrganisationQuery($organisation),
            defaultSortField: 'u.name',
        );

        /** @var User $user */
        $user = $this->getUser();
        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_ADMIN')) {
            $activeAdminsPaginator = $this->paginatorFactory->createForQuery(
                key: 'aa',
                query: $this->repository->findActiveAdminsQuery(),
                defaultSortField: 'u.name',
            );

            $deactivatedAdminsPaginator = $this->paginatorFactory->createForQuery(
                key: 'da',
                query: $this->repository->findDeactivatedAdminsQuery(),
                defaultSortField: 'u.name',
            );
        } else {
            $activeAdminsPaginator = null;
            $deactivatedAdminsPaginator = null;
        }

        return $this->render('admin/user/index.html.twig', [
            'activeUsers' => $activeUsersPaginator,
            'deactivatedUsers' => $deactivatedUsersPaginator,
            'activeAdmins' => $activeAdminsPaginator,
            'deactivatedAdmins' => $deactivatedAdminsPaginator,
            'role_descriptions' => Roles::roleDescriptions(),
        ]);
    }

    #[Route('/balie/gebruiker/new', name: 'app_admin_user_create', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.user.create')]
    public function create(Request $request): Response
    {
        $userForm = $this->createForm(UserCreateFormType::class);

        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            /** @var array{name: string, email: string, roles: string[], organisation: Organisation} $data */
            $data = $userForm->getData();
            ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
                name: $data['name'],
                email: $data['email'],
                roles: $data['roles'],
                organisation: $this->authorizationMatrix->getActiveOrganisation(),
            );

            // We need to save the user id and password in the session so we can show it on the next page.
            // This is not ideal, but the session is encrypted and the password needs to be changed on first
            // time use.
            $request->getSession()->set(
                self::RESET_USER_KEY,
                [
                    'user_id' => $user->getId(),
                    'plainTextPassword' => $plainPassword,
                ]
            );

            return $this->render('admin/user/created.html.twig', [
                'mode' => 'created',
                'user' => $user,
            ]);
        }

        return $this->render('admin/user/create.html.twig', [
            'userForm' => $userForm->createView(),
        ]);
    }

    #[Route('/balie/gebruiker/pdf', name: 'app_admin_user_pdf', methods: ['GET'])]
    #[IsGranted('AuthMatrix.user.update')]
    public function viewUserCredentials(Request $request): Response
    {
        if (! $request->getSession()->has(self::RESET_USER_KEY)) {
            throw $this->createNotFoundException();
        }

        // Remove the data from the session as soon as possible
        $data = $request->getSession()->get(self::RESET_USER_KEY);
        $request->getSession()->remove('reset_user');

        /** @var array{user_id: int, plainTextPassword: string} $data */
        $user = $this->repository->find($data['user_id']);
        if (! $user) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/user/credentials.html.twig', [
            'user' => $user,
            'password' => $data['plainTextPassword'],
            'qrcode' => $this->userService->get2faQrCodeImage($user),
        ]);
    }

    #[Route('/balie/gebruiker/{id}', name: 'app_admin_user', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.user.update')]
    public function modify(Request $request, User $user): Response
    {
        $loggedInUser = $this->getUser();

        if ($user === $loggedInUser) {
            $this->addFlash('backend', ['danger' => $this->translator->trans('admin.user.error.edit_own_account_not_allowed')]);

            return $this->redirectToRoute('app_admin_users');
        }

        if (
            $this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY)
            && $user->getOrganisation() !== $this->authorizationMatrix->getActiveOrganisation()
        ) {
            $this->addFlash('backend', ['danger' => $this->translator->trans('admin.user.error.edit_other_organisation_account_not_allowed')]);

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->hasRole(Roles::ROLE_SUPER_ADMIN) && ! $this->security->isGranted('AuthMatrix.super_admin.update')) {
            $this->addFlash('backend', ['danger' => $this->translator->trans('admin.user.error.edit_super_admin_not_allowed')]);

            return $this->redirectToRoute('app_admin_users');
        }

        $userInfoForm = $this->createForm(UserInfoFormType::class, $user);
        $userResetPasswordForm = $this->createForm(ResetPasswordFormType::class);
        $userResetTwoFactorAuthForm = $this->createForm(ResetTwoFactorAuthFormType::class);
        $userDisableForm = $this->createForm(DisableUserFormType::class);
        $userEnableForm = $this->createForm(EnableUserFormType::class);

        // Handle all forms that may be submitted
        $response = $this->handleInfoForm($userInfoForm, $request, $user) ??
            $this->handleResetPasswordForm($userResetPasswordForm, $request, $user) ??
            $this->handleResetTwoFactorAuthForm($userResetTwoFactorAuthForm, $request, $user) ??
            $this->handleDisableForm($userDisableForm, $request, $user) ??
            $this->handleEnableForm($userEnableForm, $request, $user)
        ;
        if ($response) {
            return $response;
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'user_info' => $userInfoForm->createView(),
            'user_reset_password' => $userResetPasswordForm->createView(),
            'user_reset_two_factor_auth' => $userResetTwoFactorAuthForm->createView(),
            'user_disable' => $userDisableForm->createView(),
            'user_enable' => $userEnableForm->createView(),
        ]);
    }

    protected function handleInfoForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $unchangedUser = clone $user;

        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();

        /** @var string[] $roles */
        $roles = $form->get('roles')->getData();

        $this->userService->updateRoles($loggedInUser, $unchangedUser, $user, $roles);

        $this->addFlash('backend', ['success' => $this->translator->trans('admin.user.user_modified')]);

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleDisableForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var User $actor */
        $actor = $this->getUser();
        $this->userService->disable($user, $actor);

        $userName = $user->getName();
        $this->addFlash('backend', ['success' => $this->translator
            ->trans('admin.user.user_deactivated', ['{name}' => $userName])]);

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleEnableForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var User $actor */
        $actor = $this->getUser();
        $this->userService->enable($user, $actor);

        $userName = $user->getName();
        $this->addFlash(
            'backend',
            ['success' => $this->translator->trans('admin.user.user_activated', ['{name}' => $userName])]
        );

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleResetPasswordForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        $password = $this->userService->resetPassword($user);

        // We need to save the user id and password in the session so we can show it on the next page.
        // This is not ideal, but the session is encrypted and the password needs to be changed on first
        // time use.
        $request->getSession()->set(
            self::RESET_USER_KEY,
            [
                'user_id' => $user->getId(),
                'plainTextPassword' => $password,
            ]
        );

        return $this->render('admin/user/created.html.twig', [
            'mode' => 'modified',
            'user' => $user,
        ]);
    }

    protected function handleResetTwoFactorAuthForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        $this->userService->resetTwoFactorAuth($user);

        // We need to save the user id and password in the session so we can show it on the next page.
        // This is not ideal, but the session is encrypted and the password needs to be changed on first
        // time use.
        $request->getSession()->set(
            self::RESET_USER_KEY,
            [
                'user_id' => $user->getId(),
                'plainTextPassword' => '',
            ]
        );

        return $this->render('admin/user/created.html.twig', [
            'mode' => 'modified',
            'user' => $user,
        ]);
    }
}
