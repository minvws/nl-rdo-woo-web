<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

readonly class DeleteElasticDocumentCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
