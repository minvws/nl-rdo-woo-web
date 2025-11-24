<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\RequestForAdvice;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

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
