<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
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
