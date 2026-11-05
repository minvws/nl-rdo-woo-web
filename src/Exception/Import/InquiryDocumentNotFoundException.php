<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class InquiryDocumentNotFoundException extends ImportException
{
    public const string MESSAGE_KEY = 'public.global.no_document_number';

    public function __construct(public readonly string $documentNumber)
    {
        parent::__construct(sprintf('Document %s does not exist', $documentNumber));
    }

    public function getMessageKey(): string
    {
        return InquiryDocumentNotFoundException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{documentNumber}' => $this->documentNumber,
        ];
    }
}
