<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\EntityWithFileInfo;
use Doctrine\ORM\NoResultException;
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
        try {
            $attachment = $this->attachmentRepository->findOneForDossier($dossier->getId(), Uuid::fromString($id));
        } catch (NoResultException $e) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id, previous: $e);
        }

        return $attachment;
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // No additional checks needed
        return $this->getEntityForPublicUse($dossier, $id);
    }
}
