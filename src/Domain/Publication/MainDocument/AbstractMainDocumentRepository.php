<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Attachment\AbstractAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractAttachment>
 *
 * @method AbstractMainDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractMainDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractMainDocument[]    findAll()
 * @method AbstractMainDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractMainDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractMainDocument::class);
    }
}
