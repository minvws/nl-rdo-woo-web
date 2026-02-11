<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument;

use Doctrine\Persistence\ManagerRegistry;
use Override;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
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

    #[Override]
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
