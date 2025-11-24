<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Attachment\Repository\AbstractAttachmentRepository;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;

/**
 * @extends AbstractAttachmentRepository<DispositionAttachment>
 */
class DispositionAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DispositionAttachment::class);
    }
}
