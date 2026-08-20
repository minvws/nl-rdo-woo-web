<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\DraftDecision;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Attachment\Repository\AbstractAttachmentRepository;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;

/**
 * @extends AbstractAttachmentRepository<DraftDecisionAttachment>
 */
class DraftDecisionAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DraftDecisionAttachment::class);
    }
}
