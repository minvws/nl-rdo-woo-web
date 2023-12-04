<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\AuthMatrix;
use App\Entity\Organisation;
use App\Entity\User;
use App\Service\Security\OrganisationSwitcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrganisationSwitchController extends AbstractController
{
    public function __construct(
        private readonly OrganisationSwitcher $organisationSwitcher,
    ) {
    }

    #[Route('/balie/organisatie-wissel/{id}', name: 'app_admin_switch_organisation', methods: ['GET'])]
    #[AuthMatrix('organisation.read')]
    public function index(Organisation $organisation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->organisationSwitcher->switchToOrganisation($user, $organisation);

        return $this->redirect($this->generateUrl('app_admin_index'));
    }
}
