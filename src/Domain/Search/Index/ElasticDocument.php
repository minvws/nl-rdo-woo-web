<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

readonly class ElasticDocument
{
    /**
     * @param array<array-key, mixed> $fields
     */
    public function __construct(
        private string $id,
        private ElasticDocumentType $type,
        private array $fields,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
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
