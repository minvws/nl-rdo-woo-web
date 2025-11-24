<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
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

    #[\Override]
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
