<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractMainDocumentRepository<WooDecisionMainDocument>
 */
class WooDecisionMainDocumentRepository extends AbstractMainDocumentRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooDecisionMainDocument::class);
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): WooDecisionMainDocument
    {
        Assert::isInstanceOf($dossier, WooDecision::class);

        return new WooDecisionMainDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            language: $command->language,
        );
    }
}
