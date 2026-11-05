<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class MissingMatterAndPublicationContextException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.matter_or_publication_context_required';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('Missing Matter and publication context in inventory row #%d', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return MissingMatterAndPublicationContextException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{rownumber}' => strval($this->rowIndex),
        ];
    }
}
