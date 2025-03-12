<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication;

use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractMainDocumentRepository<OtherPublicationMainDocument>
 */
class OtherPublicationMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtherPublicationMainDocument::class);
    }
}
