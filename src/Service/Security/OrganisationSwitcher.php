<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use RuntimeException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

use function reset;
use function strval;

class OrganisationSwitcher
{
    private const string SESSION_KEY = 'organisation';

    public function __construct(
        private readonly OrganisationRepository $repository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getActiveOrganisation(User $user): Organisation
    {
        if (! $this->isSwitchAllowed($user)) {
            return $user->getOrganisation();
        }

        $session = $this->requestStack->getSession();
        if (! $session->has(self::SESSION_KEY)) {
            $defaultOrganisation = $this->getDefaultOrganisation($user);

            $session->set(self::SESSION_KEY, $defaultOrganisation->getId()->toRfc4122());

            return $defaultOrganisation;
        }

        $id = Uuid::fromRfc4122(strval($session->get(self::SESSION_KEY)));
        $organisation = $this->repository->find($id);
        if (! $organisation) {
            return $this->getDefaultOrganisation($user);
        }

        return $organisation;
    }

    /**
     * @return Organisation[]
     */
    public function getOrganisations(User $user): array
    {
        if (! $this->isSwitchAllowed($user)) {
            return [$user->getOrganisation()];
        }

        return $this->repository->getAllSortedByName();
    }

    public function switchToOrganisation(User $user, Organisation $organisation): void
    {
        if (! $this->isSwitchAllowed($user)) {
            throw new AccessDeniedException('Only super-admins are allowed to switch organisations');
        }

        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $organisation->getId()->toRfc4122());
    }

    public function isSwitchAllowed(User $user): bool
    {
        return $user->hasRole(Roles::ROLE_SUPER_ADMIN);
    }

    public function getDefaultOrganisation(User $user): Organisation
    {
        $organisations = $this->getOrganisations($user);
        $defaultOrganisation = reset($organisations);
        if (! $defaultOrganisation instanceof Organisation) {
            throw new RuntimeException('Could not select a default organisation');
        }

        return $defaultOrganisation;
    }
}
