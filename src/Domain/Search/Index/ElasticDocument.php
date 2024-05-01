<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

readonly class ElasticDocument
{
    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(
        private ElasticDocumentType $type,
        private array $fields,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFieldValues(): array
    {
        return array_merge(
            $this->fields,
            ['type' => $this->type],
        );
    }
}
