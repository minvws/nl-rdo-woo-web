<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\RequestForAdvice;

use App\Domain\Publication\Attachment\Repository\AbstractAttachmentRepository;
use App\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractAttachmentRepository<RequestForAdviceAttachment>
 */
class RequestForAdviceAttachmentRepository extends AbstractAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestForAdviceAttachment::class);
    }
}
