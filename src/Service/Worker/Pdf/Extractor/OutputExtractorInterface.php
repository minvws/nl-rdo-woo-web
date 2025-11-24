<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

/**
 * This interface can be used to communicate data that is extracted from a PDF document.
 * This could be handy in case an extractor doesn't generate an output file.
 *
 * For instance, this is the case for the PageCountExtractor, which only generates a number.
 * To make this consistent with the other extractors, we use this interface to communicate the output.
 *
 * @template TOutput of object
 */
interface OutputExtractorInterface
{
    /** @return ?TOutput */
    public function getOutput(): ?object;
}
