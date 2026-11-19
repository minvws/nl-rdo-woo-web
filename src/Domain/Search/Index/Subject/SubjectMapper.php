<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Index\Schema\ElasticObjectField;

use function sprintf;

readonly class SubjectMapper
{
    public function __construct(
        private MarkdownFormatter $markdownFormatter,
    ) {
    }

    public function map(Subject $subject): ElasticDocument
    {
        $id = ElasticDocumentId::forSubject($subject);

        return new ElasticDocument(
            $id,
            ElasticDocumentType::SUBJECT,
            null,
            [
                ElasticField::ID->value => $id,
                ElasticField::TITLE->value => $subject->getLandingPageTitle()?->toString() ?? $subject->getName(),
                ElasticField::SUMMARY->value => $this->mapSummary($subject->getLandingPageDescription()),
                ElasticField::STATUS->value => $subject->getLandingPageStatus()->value,
                ElasticObjectField::SUBJECT->value => [
                    ElasticField::ID->value => $subject->getId(),
                    ElasticField::NAME->value => $subject->getName(),
                ],
                ElasticField::ORGANISATION_IDS->value => [$subject->getOrganisation()->getId()],
                ElasticNestedField::CONTENT_TREE->value => $this->mapContentTree($subject->getLandingPageContentTree()),
                ElasticField::HAS_VISIBLE_CONTENT_TREE->value => $subject->hasVisibleLandingPageContentTree(),
            ],
        );
    }

    private function mapSummary(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        return $this->markdownFormatter->toPlainText($description);
    }

    /**
     * @return list<array<string, string>>
     */
    private function mapContentTree(?SubjectContentTree $contentTree): array
    {
        if ($contentTree === null) {
            return [];
        }

        $entries = [];
        $entries[] = $this->mapContentTreeEntry(
            $contentTree->title,
            sprintf("%s\n\n%s", $contentTree->intro, $contentTree->outro),
        );

        foreach ($this->flattenContentNodes($contentTree->children) as $contentNode) {
            $entries[] = $this->mapContentTreeEntry($contentNode->title, $contentNode->body);
        }

        return $entries;
    }

    /**
     * @param list<SubjectContentNode> $contentNodes
     *
     * @return list<SubjectContentNode>
     */
    private function flattenContentNodes(array $contentNodes): array
    {
        $flattenedContentNodes = [];
        foreach ($contentNodes as $contentNode) {
            $flattenedContentNodes[] = $contentNode;

            foreach ($this->flattenContentNodes($contentNode->children) as $childContentNode) {
                $flattenedContentNodes[] = $childContentNode;
            }
        }

        return $flattenedContentNodes;
    }

    /**
     * @return array<string, string>
     */
    private function mapContentTreeEntry(string $title, string $body): array
    {
        return [
            ElasticField::TITLE->value => $title,
            ElasticField::BODY->value => $this->markdownFormatter->toPlainText($body),
        ];
    }
}
