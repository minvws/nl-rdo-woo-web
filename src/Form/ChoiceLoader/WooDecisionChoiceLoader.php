<?php

declare(strict_types=1);

namespace App\Form\ChoiceLoader;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Entity\User;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This will load all WooDecisions that a user is allowed to see / select.
 */
class WooDecisionChoiceLoader implements ChoiceLoaderInterface
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly Security $security,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (! $user) {
            return new ArrayChoiceList([]);
        }

        $entities = $this->wooDecisionRepository->findAllForOrganisation(
            $this->authorizationMatrix->getActiveOrganisation()
        );

        $choices = [];
        foreach ($entities as $entity) {
            $choices[$entity->getTitle()] = (string) $entity->getId();
        }

        return new ArrayChoiceList($choices);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $choices = [];
        foreach ($values as $choice) {
            if (! $choice) {
                continue;
            }
            $choices[] = $this->wooDecisionRepository->find(Uuid::fromString($choice));
        }

        return $choices;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        $values = [];
        foreach ($choices as $choice) {
            /** @var WooDecision|null $choice */
            if ($choice === null || empty($choice)) {
                continue;
            }

            $values[] = (string) $choice->getId();
        }

        return $values;
    }
}
