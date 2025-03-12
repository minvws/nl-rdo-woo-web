<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\Attachment\Repository\AbstractAttachmentRepository;
use App\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractAttachmentRepository<AdviceAttachment>
 */
class AdviceAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdviceAttachment::class);
    }
}
