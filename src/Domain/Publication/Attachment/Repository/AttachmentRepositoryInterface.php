<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Repository;

use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

interface AttachmentRepositoryInterface
{
    public function save(AbstractAttachment $entity, bool $flush = false): void;

    public function remove(AbstractAttachment $entity, bool $flush = false): void;

    public function create(AbstractDossier $dossier, CreateAttachmentCommand $command): AbstractAttachment;

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AbstractAttachment;

    /**
     * @return ArrayCollection<array-key,covariant AbstractAttachment>
     */
    public function findAllForDossier(Uuid $dossierId): ArrayCollection;

    public function findOneForDossier(Uuid $dossierId, Uuid $id): AbstractAttachment;
}
