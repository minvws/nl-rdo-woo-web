<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

readonly class ElasticDocument
{
    /**
     * @param array<array-key, mixed> $fields
     */
    public function __construct(
        private ElasticDocumentType $type,
        private array $fields,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getDocumentValues(): array
    {
        return array_merge(
            $this->fields,
            ['type' => $this->type],
        );
    }
}
