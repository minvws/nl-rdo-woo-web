<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\RequestForAdvice;

use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractMainDocumentRepository<RequestForAdviceMainDocument>
 */
class RequestForAdviceMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestForAdviceMainDocument::class);
    }
}
