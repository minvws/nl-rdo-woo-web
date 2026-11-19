<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Subject;

use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\ResultEntryInterface;

readonly class SubjectSearchResultEntry implements ResultEntryInterface
{
    /**
     * @param array<array-key, string> $highlights
     */
    public function __construct(
        private SubjectSearchResult $subject,
        private array $highlights,
    ) {
    }

    public function getType(): ElasticDocumentType
    {
        return ElasticDocumentType::SUBJECT;
    }

    public function getSubject(): SubjectSearchResult
    {
        return $this->subject;
    }

    /**
     * @return array<array-key, string>
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }
}
