<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\DraftDecision;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

/**
 * @extends AbstractMainDocumentRepository<DraftDecisionMainDocument>
 */
class DraftDecisionMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DraftDecisionMainDocument::class);
    }
}
