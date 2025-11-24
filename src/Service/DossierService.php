<?php

declare(strict_types=1);

namespace Shared\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class DossierService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private WizardStatusFactory $statusFactory,
        private SearchDispatcher $searchDispatcher,
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
        $this->doctrine->persist($dossier);

        if ($flush) {
            $this->doctrine->flush();
        }

        return $completed;
    }

    /**
     * @deprecated to be removed in woo-2066
     */
    public function handleEntityUpdate(AbstractDossier $dossier): void
    {
        if ($dossier->getStatus() === DossierStatus::DELETED) {
            return;
        }

        $this->validateCompletion($dossier);

        $this->searchDispatcher->dispatchIndexDossierCommand($dossier->getId());
    }

    /**
     * @throws ValidationFailedException
     */
    public function validate(AbstractDossier $dossier): void
    {
        $errors = $this->validator->validate($dossier, groups: \array_column(DossierValidationGroup::cases(), 'value'));

        if ($errors->count() > 0) {
            throw new ValidationFailedException($dossier, $errors);
        }
    }

    public function isApiUpdateAllowed(AbstractDossier $dossier): bool
    {
        return $dossier->getStatus()->isNewOrConcept();
    }
}
