<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Attachment\Repository\AbstractAttachmentRepository;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;

/**
 * @extends AbstractAttachmentRepository<CovenantAttachment>
 */
class CovenantAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CovenantAttachment::class);
    }
}
