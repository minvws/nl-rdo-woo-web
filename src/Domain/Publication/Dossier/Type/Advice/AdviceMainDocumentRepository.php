<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractMainDocumentRepository<AdviceMainDocument>
 */
class AdviceMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdviceMainDocument::class);
    }
}
