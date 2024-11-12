<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractMainDocumentRepository<CovenantMainDocument>
 */
class CovenantMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CovenantMainDocument::class);
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        Assert::isInstanceOf($dossier, Covenant::class);

        return new CovenantMainDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            language: $command->language,
        );
    }
}
