<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\SearchDispatcher;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\DossierWizard\WizardStatusFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DossierService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private MessageBusInterface $messageBus,
        private WizardStatusFactory $statusFactory,
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    /**
     * @deprecated to be removed in woo-3391
     */
    public function generateArchives(WooDecision $dossier): void
    {
        if ($dossier->getStatus()->isNewOrConcept()) {
            return;
        }

        $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));
    }

    /**
     * Validate dossier completion and set dossier completed flag.
     */
    public function validateCompletion(AbstractDossier $dossier, bool $flush = true): bool
    {
        $completed = $this->statusFactory->getWizardStatus($dossier, StepName::DETAILS, false)->isCompleted();

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
}
