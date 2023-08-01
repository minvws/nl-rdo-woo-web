<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;

/**
 * This interface can be used to communicate data that is extracted from a PDF document.
 * This could be handy in case an extractor doesn't generate an output file.
 *
 * For instance, this is the case for the PageCountExtractor, which only generates a number.
 * To make this consistent with the other extractors, we use this interface to communicate the output.
 *
 * We retrieve output based on document/pagenr, so it is actually possibly to store multiple outputs in case this is needed.
 */
interface OutputExtractorInterface
{
    /** @return mixed[] */
    public function getOutput(Document $document, int $pageNr): array;
}
