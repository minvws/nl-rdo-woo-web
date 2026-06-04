<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use JsonSerializable;

final readonly class ProblemDetails implements JsonSerializable
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public ?string $field = null,
        public ?string $keyword = null,
        public ?string $format = null,
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
        ];

        if ($this->field !== null) {
            $data['field'] = $this->field;
        }

        if ($this->keyword !== null) {
            $data['keyword'] = $this->keyword;
        }

        if ($this->format !== null) {
            $data['format'] = $this->format;
        }

        return $data;
    }
}
