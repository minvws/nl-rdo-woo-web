<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Organisation;
use App\Entity\User;
use App\Form\User\DisableUserFormType;
use App\Form\User\EnableUserFormType;
use App\Form\User\ResetCredentialsFormType;
use App\Form\User\UserCreateFormType;
use App\Form\User\UserInfoFormType;
use App\Repository\UserRepository;
use App\Roles;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
use App\Service\UserService;
use Knp\Component\Pager\PaginatorInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    protected const RESET_USER_KEY = 'reset_user';

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
        private readonly AuditLogger $auditLogger,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly PaginatorInterface $paginator,
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

        /** @var User $user */
        $user = $this->getUser();

        $pagination = $this->paginator->paginate(
            $this->repository->findAllForOrganisationQuery(
                $this->authorizationMatrix->getActiveOrganisation(),
                $user->hasRole(Roles::ROLE_SUPER_ADMIN),
            ),
            $request->query->getInt('page', 1),
            100,
            [
                PaginatorInterface::DEFAULT_SORT_FIELD_NAME => 'u.name',
            ],
        );

        $roles = Roles::roleDetails();
        $roleDetails = [];
        foreach ($roles as $role) {
            $roleDetails[$role['role']] = $role['description'];
        }

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
            'role_details' => $roleDetails,
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
            throw new NotFoundHttpException();
        }

        // Remove the data from the session as soon as possible
        $data = $request->getSession()->get(self::RESET_USER_KEY);
        $request->getSession()->remove('reset_user');

        /** @var array{user_id: int, plainTextPassword: string} $data */
        $user = $this->repository->find($data['user_id']);
        if (! $user) {
            throw new NotFoundHttpException();
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
        /** @var User $loggedInUser */
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

        $userInfoForm = $this->createForm(UserInfoFormType::class, $user);
        $userResetForm = $this->createForm(ResetCredentialsFormType::class);
        $userDisableForm = $this->createForm(DisableUserFormType::class);
        $userEnableForm = $this->createForm(EnableUserFormType::class);

        // Handle all forms that may be submitted
        $response = $this->handleInfoForm($userInfoForm, $request, $user) ??
            $this->handleResetForm($userResetForm, $request, $user) ??
            $this->handleDisableForm($userDisableForm, $request, $user) ??
            $this->handleEnableForm($userEnableForm, $request, $user)
        ;
        if ($response) {
            return $response;
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'user_info' => $userInfoForm->createView(),
            'user_reset_password' => $userResetForm->createView(),
            'user_reset_2fa' => $userResetForm->createView(),
            'user_disable' => $userDisableForm->createView(),
            'user_enable' => $userEnableForm->createView(),
        ]);
    }

    protected function handleInfoForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $oldUser = clone $user;

        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var LoggableUser $loggedInUser */
        $loggedInUser = $this->getUser();

        /** @var string[] $roles */
        $roles = $form->get('roles')->getData();
        /** @var LoggableUser $loggedInUser */
        $this->userService->updateRoles($loggedInUser, $user, $roles);

        $this->addFlash('backend', ['success' => $this->translator->trans('admin.user.user_modified')]);

        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_USERDATA)
            ->withData([
                'user_id' => $user->getAuditId(),
            ])
            ->withPiiData([
                'old' => [
                    'name' => $oldUser->getName(),
                    'email' => $oldUser->getEmail(),
                ],
                'new' => [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ],
            ]));

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleDisableForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        $user->setEnabled(false);
        $this->repository->save($user, true);

        // User name.
        $userName = $user->getName();
        $this->addFlash('backend', ['success' => $this->translator
            ->trans('admin.user.user_deactivated', ['{name}' => $userName])]);

        /** @var LoggableUser $loggedInUser */
        $loggedInUser = $this->getUser();
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ACTIVE)
            ->withData([
                'user_id' => $user->getAuditId(),
                'enabled' => false,
            ]));

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleEnableForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        $user->setEnabled(true);
        $this->repository->save($user, true);

        $userName = $user->getName();
        $this->addFlash(
            'backend',
            ['success' => $this->translator->trans('admin.user.user_activated', ['{name}' => $userName])]
        );

        /** @var LoggableUser $loggedInUser */
        $loggedInUser = $this->getUser();
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ACTIVE)
            ->withData([
                'user_id' => $user->getAuditId(),
                'enabled' => true,
            ]));

        return $this->redirectToRoute('app_admin_users');
    }

    protected function handleResetForm(FormInterface $form, Request $request, User $user): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        $password = $this->userService->resetCredentials(
            $user,
            resetPassword: boolval($form->get('reset_pw')->getData()),
            reset2fa: boolval($form->get('reset_2fa')->getData())
        );

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
}
