<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Entity\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

readonly class AttachmentDossierFileProvider implements DossierFileProviderInterface
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getType(): DossierFileType
    {
        return DossierFileType::ATTACHMENT;
    }

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        $attachment = $this->attachmentRepository->findOneOrNullForDossier($dossier->getId(), Uuid::fromString($id));
        if ($attachment === null) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
        }

        return $attachment;
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // No additional checks needed
        return $this->getEntityForPublicUse($dossier, $id);
    }
}
