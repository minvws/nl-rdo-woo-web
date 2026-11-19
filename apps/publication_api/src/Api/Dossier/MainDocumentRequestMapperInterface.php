<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

/**
 * @template TDossier of AbstractDossier
 * @template TMainDocumentRequestDto of MainDocumentRequestDtoInterface
 */
interface MainDocumentRequestMapperInterface
{
    /**
     * @param TDossier $dossier
     * @param TMainDocumentRequestDto $mainDocumentRequestDto
     */
    public function create(AbstractDossier $dossier, MainDocumentRequestDtoInterface $mainDocumentRequestDto): AbstractMainDocument;

    /**
     * @param TDossier $dossier
     * @param TMainDocumentRequestDto $mainDocumentRequestDto
     */
    public function update(AbstractDossier $dossier, MainDocumentRequestDtoInterface $mainDocumentRequestDto): AbstractMainDocument;
}
