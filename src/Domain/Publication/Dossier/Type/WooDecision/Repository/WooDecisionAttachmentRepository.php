<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionAttachment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractAttachmentRepository<WooDecisionAttachment>
 */
class WooDecisionAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooDecisionAttachment::class);
    }
}
