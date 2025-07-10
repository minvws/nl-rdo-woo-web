<?php

declare(strict_types=1);

namespace App\Form\ChoiceLoader;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This will load all dossiers that a user is allowed to see / select.
 */
class DocumentPrefixChoiceLoader implements ChoiceLoaderInterface
{
    public function __construct(
        protected EntityManagerInterface $doctrine,
        protected AuthorizationMatrix $authorizationMatrix,
        protected Security $security,
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

        $prefixes = $this->authorizationMatrix->getActiveOrganisation()->getDocumentPrefixes();

        $choices = [];
        foreach ($prefixes as $entity) {
            $choices[$entity->getPrefix()] = (string) $entity->getId();
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
            $choices[] = $this->doctrine->getRepository(DocumentPrefix::class)->find(Uuid::fromString($choice));
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
            /** @var DocumentPrefix|null $choice */
            if ($choice === null || empty($choice)) {
                continue;
            }

            $values[] = (string) $choice->getId();
        }

        return $values;
    }
}
