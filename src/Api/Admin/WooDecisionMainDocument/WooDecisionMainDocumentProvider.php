<?php

declare(strict_types=1);

namespace App\Api\Admin\WooDecisionMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionMainDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class WooDecisionMainDocumentProvider extends AbstractMainDocumentProvider
{
    public function __construct(
        private WooDecisionMainDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return WooDecisionMainDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
