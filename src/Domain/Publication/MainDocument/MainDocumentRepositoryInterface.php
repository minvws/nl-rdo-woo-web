<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Symfony\Component\Uid\Uuid;

interface MainDocumentRepositoryInterface
{
    public function save(AbstractMainDocument $entity, bool $flush = false): void;

    public function remove(AbstractMainDocument $entity, bool $flush = false): void;

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument;

    public function findOneByDossierId(Uuid $dossierId): ?AbstractMainDocument;
}
