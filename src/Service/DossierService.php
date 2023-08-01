<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dossier;
use App\Message\UpdateDossierMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class handles dossier management.
 */
class DossierService
{
    protected EntityManagerInterface $doctrine;
    protected InventoryService $inventoryService;
    protected LoggerInterface $logger;
    protected MessageBusInterface $messageBus;

    public function __construct(
        EntityManagerInterface $doctrine,
        InventoryService $inventoryService,
        MessageBusInterface $messageBus,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->inventoryService = $inventoryService;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
    }

    /**
     * Creates a new dossier with inventory file. Returns an array of errors, if any.
     *
     * There can be multiple errors per row/line number. Note that line number 0 means
     * a generic error
     *
     * [
     *   0 => [
     *           "incorrect column count",
     *        ]
     *   4 => [
     *           "invalid date format",
     *           "invalid other error",
     *        ]
     * ]
     *
     * @return array<int, string[]>
     */
    public function create(Dossier $dossier, UploadedFile $file = null): array
    {
        $dossier->setCreatedAt(new \DateTimeImmutable());
        $dossier->setUpdatedAt(new \DateTimeImmutable());
        $dossier->setStatus(Dossier::STATUS_CONCEPT);

        // @TODO: Hardcoded dossier prefix!
        $dossierNr = 'VWS-' . random_int(100, 999) . '-' . random_int(1000, 9999);
        $dossier->setDossierNr($dossierNr);

        // Wrap in transaction, so we can rollback if inventory processing fails
        $this->doctrine->beginTransaction();

        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        if ($file) {
            $errors = $this->inventoryService->processInventory($file, $dossier);
        } else {
            $errors = [];
        }

        if (count($errors)) {
            // Rollback inventory and dossier changes
            $this->doctrine->rollback();

            $this->logger->info('Dossier creation failed', [
                'dossier' => $dossier->getId(),
                'errors' => $errors,
            ]);

            return $errors;
        }

        // Commit inventory and dossier changes
        $this->doctrine->commit();

        $this->logger->info('Dossier created', [
            'dossier' => $dossier->getId(),
        ]);

        return [];
    }

    /**
     * @return array<int, string[]>
     */
    public function update(Dossier $dossier, UploadedFile $file = null): array
    {
        $dossier->setUpdatedAt(new \DateTimeImmutable());

        // Wrap in transaction
        $this->doctrine->beginTransaction();

        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        $errors = [];
        if ($file) {
            $errors = $this->inventoryService->processInventory($file, $dossier);
        }

        if ($errors) {
            // Rollback everything mutated in this transaction
            $this->doctrine->rollback();

            $this->logger->info('Dossier update failed', [
                'dossier' => $dossier->getId(),
                'errors' => $errors,
            ]);

            return $errors;
        }

        // Commit inventory and dossier changes
        $this->doctrine->commit();

        $this->messageBus->dispatch(new UpdateDossierMessage($dossier->getId()));

        $this->logger->info('Dossier updated', [
            'dossier' => $dossier->getId(),
        ]);

        return [];
    }

    public function remove(Dossier $dossier): void
    {
        // Remove documents that are only attached to this dossier
        foreach ($dossier->getDocuments() as $document) {
            if ($document->getDossiers()->count() == 1) {
                $this->doctrine->remove($document);
            }
        }

        // @TODO: remove from elasticsearch

        $this->doctrine->remove($dossier);
        $this->doctrine->flush();
    }

    public function changeState(Dossier $dossier, string $newState): void
    {
        if (! $dossier->isAllowedState($newState)) {
            $this->logger->error('Invalid state change', [
                'dossier' => $dossier->getId(),
                'oldState' => $dossier->getStatus(),
                'newState' => $newState,
                'reason' => 'Invalid state',
            ]);

            throw new \InvalidArgumentException('Invalid state change');
        }

        switch ($newState) {
            case Dossier::STATUS_COMPLETED:
                // Check all documents present
                foreach ($dossier->getDocuments() as $document) {
                    if (! $document->isUploaded()) {
                        $this->logger->error('Invalid state change', [
                            'dossier' => $dossier->getId(),
                            'oldState' => $dossier->getStatus(),
                            'newState' => $newState,
                            'reason' => 'Not all documents uploaded',
                        ]);

                        throw new \InvalidArgumentException('Not all documents are uploaded in this dossier');
                    }
                }
                break;
        }

        // Set new status
        $dossier->setStatus($newState);
        $this->doctrine->flush();

        $this->messageBus->dispatch(new UpdateDossierMessage($dossier->getId()));

        $this->logger->info('Dossier state changed', [
            'dossier' => $dossier->getId(),
            'oldState' => $dossier->getStatus(),
            'newState' => $newState,
        ]);
    }
}
