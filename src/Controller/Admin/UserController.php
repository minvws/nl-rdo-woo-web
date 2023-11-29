<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\Organisation;
use App\Entity\User;
use App\Form\User\DisableUserFormType;
use App\Form\User\EnableUserFormType;
use App\Form\User\ResetCredentialsFormType;
use App\Form\User\UserCreateFormType;
use App\Form\User\UserInfoFormType;
use App\Roles;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected UserService $userService;
    protected TranslatorInterface $translator;
    protected AuditLogger $auditLogger;
    protected AuthorizationMatrix $authorizationMatrix;

    protected const RESET_USER_KEY = 'reset_user';

    public function __construct(
        EntityManagerInterface $doctrine,
        UserService $userService,
        TranslatorInterface $translator,
        AuditLogger $auditLogger,
        AuthorizationMatrix $authorizationMatrix
    ) {
        $this->doctrine = $doctrine;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->auditLogger = $auditLogger;
        $this->authorizationMatrix = $authorizationMatrix;
    }

    #[Route('/balie/gebruikers', name: 'app_admin_users', methods: ['GET'])]
    #[AuthMatrix('user.read')]
    public function index(Request $request, Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('User management');

        // Remove any reset data from the session as soon as we return back to the user list. This is not
        // 100% foolproof but it should work in most cases. In cases it doesn't, there is nothing wrong as
        // the data is never shown to the user.
        if ($request->getSession()->has(self::RESET_USER_KEY)) {
            $request->getSession()->remove(self::RESET_USER_KEY);
        }

        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)) {
            /** @var User $user */
            $user = $this->getUser();
            $users = $this->doctrine->getRepository(User::class)->findAllForOrganisation($user->getOrganisation());
        } else {
            $users = $this->doctrine->getRepository(User::class)->findAll();
        }

        $roles = Roles::roleDetails();
        $roleDetails = [];
        foreach ($roles as $role) {
            $roleDetails[$role['role']] = $role['description'];
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'role_details' => $roleDetails,
        ]);
    }

    #[Route('/balie/gebruiker/new', name: 'app_admin_user_create', methods: ['GET', 'POST'])]
    #[AuthMatrix('user.create')]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('User management', 'app_admin_users');
        $breadcrumbs->addItem('New user');

        $userForm = $this->createForm(UserCreateFormType::class);

        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            // If the user has organisation only filter, we need to force set the organisation in case the user somehow
            // managed to change it in the form.
            if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY) == true) {
                /** @var User $user */
                $user = $this->getUser();
                $data['organisation'] = $user->getOrganisation();
            }

            /** @var array{name: string, email: string, roles: string[], organisation: Organisation} $data */
            $data = $userForm->getData();
            ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
                name: $data['name'],
                email: $data['email'],
                roles: $data['roles'],
                organisation: $data['organisation']
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
    #[AuthMatrix('user.update')]
    public function viewUserCredentials(Request $request): Response
    {
        if (! $request->getSession()->has(self::RESET_USER_KEY)) {
            throw new NotFoundHttpException();
        }

        // Remove the data from the session as soon as possible
        $data = $request->getSession()->get(self::RESET_USER_KEY);
        $request->getSession()->remove('reset_user');

        /** @var array{user_id: int, plainTextPassword: string} $data */
        $user = $this->doctrine->getRepository(User::class)->find($data['user_id']);
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
    #[AuthMatrix('user.update')]
    public function modify(Breadcrumbs $breadcrumbs, Request $request, User $user): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('User management', 'app_admin_users');
        $breadcrumbs->addItem('Edit user');

        if ($user === $this->getUser()) {
            $this->addFlash('backend', ['warning' => $this->translator->trans('Modifying your own account is not allowed')]);

            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY)) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            if ($user->getOrganisation() !== $currentUser->getOrganisation()) {
                $this->addFlash('backend', ['warning' => $this->translator->trans('Modifying this account is not allowed')]);

                return $this->redirectToRoute('app_admin_users');
            }
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
        /** @phpstan-ignore-next-line */
        $loggedInUser = $this->getUser();

        /** @var string[] $roles */
        $roles = $form->get('roles')->getData();
        /** @var LoggableUser $loggedInUser */
        $this->userService->updateRoles($loggedInUser, $user, $roles);

        $this->doctrine->flush();
        $this->addFlash('backend', ['success' => $this->translator->trans('The user has been modified')]);

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
        $this->doctrine->flush();

        // User name.
        $userName = $user->getName();
        $this->addFlash('backend', ['success' => $this->translator
            ->trans('Account of {name} has been disabled.', ['{name}' => $userName])]);

        /** @var LoggableUser $loggedInUser */
        /** @phpstan-ignore-next-line */
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
        $this->doctrine->flush();

        $userName = $user->getName();
        $this->addFlash(
            'backend',
            ['success' => $this->translator->trans('Account of {name} has been enabled.', ['{name}' => $userName])]
        );

        /** @var LoggableUser $loggedInUser */
        /** @phpstan-ignore-next-line */
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
