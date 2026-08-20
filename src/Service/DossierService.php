<?php

declare(strict_types=1);

namespace Shared\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class DossierService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WizardStatusFactory $statusFactory,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Validate dossier completion and set dossier completed flag.
     */
    public function validateCompletion(AbstractDossier $dossier, bool $flush = true): bool
    {
        $completed = $this->statusFactory->getWizardStatus($dossier, StepName::DETAILS, false)->isCompleted();

        if ($completed === true && $dossier instanceof WooDecision && $dossier->getStatus()->isPubliclyAvailable()) {
            $completed = ! $dossier->hasWithdrawnOrSuspendedDocuments();
        }

        $dossier->setCompleted($completed);
        $this->entityManager->persist($dossier);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $completed;
    }

    /**
     * @param array<array-key, DossierValidationGroup> $validationGroups
     *
     * @throws ValidationFailedException
     */
    public function validate(AbstractDossier $dossier, array $validationGroups): void
    {
        $errors = $this->validator->validate($dossier, groups: EnumHelper::getStringValues($validationGroups));

        if ($errors->count() > 0) {
            throw new ValidationFailedException($dossier, $errors);
        }
    }

    public function refreshDossier(AbstractDossier $dossier): void
    {
        $uow = $this->entityManager->getUnitOfWork();

        if ($this->entityManager->contains($dossier) && ! $uow->isScheduledForInsert($dossier)) {
            $this->entityManager->refresh($dossier);
        }
    }
}
