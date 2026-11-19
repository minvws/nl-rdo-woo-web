<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class InvalidJudgementException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_judgement';

    public function __construct(
        int $rowIndex,
        public readonly string $judgement,
    ) {
        parent::__construct($rowIndex, sprintf("Invalid judgement value '%s'", $judgement));
    }

    public function getMessageKey(): string
    {
        return InvalidJudgementException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{judgement}' => $this->judgement,
        ];
    }
}
