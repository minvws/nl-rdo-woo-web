<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractMainDocumentRepository<ComplaintJudgementMainDocument>
 */
class ComplaintJudgementMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplaintJudgementMainDocument::class);
    }
}
