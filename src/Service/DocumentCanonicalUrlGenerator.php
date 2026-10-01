<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\DocumentNumber;

final readonly class DocumentCanonicalUrlGenerator
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    public function canonical(DocumentNumber $documentNumber): string
    {
        return $this->publicUrlGenerator
            ->buildUrlFromRoute('app_document_canonical', [
                'documentNumber' => $documentNumber->toString(),
            ])
            ->toString();
    }
}
