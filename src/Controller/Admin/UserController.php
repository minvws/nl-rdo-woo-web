<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\User\DisableUserFormType;
use App\Form\User\EnableUserFormType;
use App\Form\User\ResetCredentialsFormType;
use App\Form\User\UserCreateFormType;
use App\Form\User\UserInfoFormType;
use App\Form\User\UserRoleFormType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    public function __construct(
        EntityManagerInterface $doctrine,
        UserService $userService,
        TranslatorInterface $translator,
        AuditLogger $auditLogger
    ) {
        $this->doctrine = $doctrine;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->auditLogger = $auditLogger;
    }

    #[Route('/balie/gebruikers', name: 'app_admin_users', methods: ['GET'])]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('User management');

        $users = $this->doctrine->getRepository(User::class)->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/balie/gebruiker/new', name: 'app_admin_user_create', methods: ['GET', 'POST'])]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('User management', 'app_admin_users');
        $breadcrumbs->addItem('New user');

        $userForm = $this->createForm(UserCreateFormType::class);

        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            /** @var array{name: string, email: string, roles: string[]} $data */
            $data = $userForm->getData();
            ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
                name: $data['name'],
                email: $data['email'],
                roles: $data['roles']
            );

            return $this->render('admin/user/credentials.html.twig', [
                'user' => $user,
                'password' => $plainPassword,
                'qrcode' => $this->userService->get2faQrCodeImage($user),
            ]);
        }

        return $this->render('admin/user/create.html.twig', [
            'userForm' => $userForm->createView(),
        ]);
    }

    #[Route('/balie/gebruiker/{id}', name: 'app_admin_user', methods: ['GET', 'POST'])]
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

        // Handle all forms that may be submitted
        $response = $this->handleInfoForm($request, $user) ??
            $this->handleRoleForm($request, $user) ??
            $this->handleResetForm($request, $user) ??
            $this->handleDisableForm($request, $user) ??
            $this->handleEnableForm($request, $user)
        ;
        if ($response) {
            return $response;
        }

        $userInfoForm = $this->createForm(UserInfoFormType::class, $user);
        $userRolesForm = $this->createForm(UserRoleFormType::class, $user);
        $userResetForm = $this->createForm(ResetCredentialsFormType::class);
        $userDisableForm = $this->createForm(DisableUserFormType::class);
        $userEnableForm = $this->createForm(EnableUserFormType::class);

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'user_info' => $userInfoForm->createView(),
            'user_roles' => $userRolesForm->createView(),
            'user_reset' => $userResetForm->createView(),
            'user_disable' => $userDisableForm->createView(),
            'user_enable' => $userEnableForm->createView(),
        ]);
    }

    protected function handleInfoForm(Request $request, User $user): ?Response
    {
        $oldUser = clone $user;

        $userInfoForm = $this->createForm(UserInfoFormType::class, $user);

        $userInfoForm->handleRequest($request);
        if (! $userInfoForm->isSubmitted() || ! $userInfoForm->isValid()) {
            return null;
        }

        $this->doctrine->flush();
        $this->addFlash('backend', ['success' => $this->translator->trans('The user has been modified')]);

        /** @var LoggableUser $loggedInUser */
        /** @phpstan-ignore-next-line */
        $loggedInUser = $this->getUser();
        /** @var LoggableUser $loggedInUser */
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

        return $this->redirectToRoute('app_admin');
    }

    protected function handleRoleForm(Request $request, User $user): ?Response
    {
        $oldUser = clone $user;
        $userRolesForm = $this->createForm(UserRoleFormType::class, $user);

        $userRolesForm->handleRequest($request);
        if (! $userRolesForm->isSubmitted() || ! $userRolesForm->isValid()) {
            return null;
        }

        $this->doctrine->flush();
        $this->addFlash('backend', ['success' => $this->translator->trans('User roles have been modified')]);

        /** @var LoggableUser $loggedInUser */
        /** @phpstan-ignore-next-line */
        $loggedInUser = $this->getUser();
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_USERDATA)
            ->withData([
                'user_id' => $user->getAuditId(),
                'old' => [
                    'roles' => $oldUser->getRoles(),
                ],
                'new' => [
                    'roles' => $user->getRoles(),
                ],
            ]));

        return $this->redirectToRoute('app_admin');
    }

    protected function handleDisableForm(Request $request, User $user): ?Response
    {
        $userDisableForm = $this->createForm(DisableUserFormType::class);

        $userDisableForm->handleRequest($request);
        if (! $userDisableForm->isSubmitted() || ! $userDisableForm->isValid()) {
            return null;
        }

        $user->setEnabled(false);
        $this->doctrine->flush();
        $this->addFlash('backend', ['success' => $this->translator->trans('User has been disabled')]);

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

        return $this->redirectToRoute('app_admin');
    }

    protected function handleEnableForm(Request $request, User $user): ?Response
    {
        $userEnableForm = $this->createForm(EnableUserFormType::class);

        $userEnableForm->handleRequest($request);
        if (! $userEnableForm->isSubmitted() || ! $userEnableForm->isValid()) {
            return null;
        }

        $user->setEnabled(true);
        $this->doctrine->flush();
        $this->addFlash('backend', ['success' => $this->translator->trans('User has been enabled')]);

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

        return $this->redirectToRoute('app_admin');
    }

    protected function handleResetForm(Request $request, User $user): ?Response
    {
        $userResetForm = $this->createForm(ResetCredentialsFormType::class);

        $userResetForm->handleRequest($request);
        if (! $userResetForm->isSubmitted() || ! $userResetForm->isValid()) {
            return null;
        }

        $password = $this->userService->resetCredentials(
            $user,
            resetPassword: boolval($userResetForm->get('reset_pw')->getData()),
            reset2fa: boolval($userResetForm->get('reset_2fa')->getData())
        );

        return $this->render('admin/user/credentials.html.twig', [
            'user' => $user,
            'password' => $password,
            'qrcode' => $this->userService->get2faQrCodeImage($user),
        ]);
    }
}
