<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;

readonly class AttachmentDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithAttachments) {
            return;
        }

        foreach ($dossier->getAttachments() as $attachment) {
            $this->deleteFileForEntity($attachment);
        }
    }
}
