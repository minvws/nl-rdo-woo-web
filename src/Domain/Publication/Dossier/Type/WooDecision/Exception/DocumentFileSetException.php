<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Exception;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;

class DocumentFileSetException extends \RuntimeException
{
    public static function forCannotStartProcessingUploads(DocumentFileSet $documentFileSet): self
    {
        return new self(sprintf(
            'Cannot start DocumentFileSet processing of uploads, id=%s, status=%s',
            $documentFileSet->getId()->toRfc4122(),
            $documentFileSet->getStatus()->value,
        ));
    }

    public static function forCannotConfirmUpdates(WooDecision|DocumentFileSet $entity): self
    {
        $message = match (true) {
            $entity instanceof WooDecision => 'Cannot confirm DocumentFileSet update because of invalid WooDecision status, id=%s, status=%s',
            $entity instanceof DocumentFileSet => 'Cannot confirm DocumentFileSet updates, id=%s, status=%s',
        };

        return new self(sprintf(
            $message,
            $entity->getId()->toRfc4122(),
            $entity->getStatus()->value,
        ));
    }

    public static function forCannotUpdateStatus(DocumentFileSet $documentFileSet): self
    {
        return new self(sprintf(
            'Cannot update status for DocumentFileSet, id=%s, status=%s',
            $documentFileSet->getId()->toRfc4122(),
            $documentFileSet->getStatus()->value,
        ));
    }

    public static function forCannotAddUpload(DocumentFileSet $documentFileSet): self
    {
        return new self(sprintf(
            'Cannot add upload for DocumentFileSet, id=%s, status=%s',
            $documentFileSet->getId()->toRfc4122(),
            $documentFileSet->getStatus()->value,
        ));
    }
}
