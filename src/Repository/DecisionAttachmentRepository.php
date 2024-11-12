<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\AbstractAttachmentRepository;
use App\Entity\DecisionAttachment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractAttachmentRepository<DecisionAttachment>
 */
class DecisionAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DecisionAttachment::class);
    }
}
