<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepth;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Symfony\Component\Validator\Constraints as Assert;

class SubjectLandingPageInputDto
{
    /**
     * @param list<SubjectContentNode> $contentTree
     */
    public function __construct(
        public LandingPageSlug $slug,
        public LandingPageTitle $title,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 10000)]
        public string $description,
        #[Assert\NotNull]
        public SubjectLandingPageStatus $status,
        #[Assert\All([new Assert\Type(SubjectContentNode::class)])]
        #[Assert\Valid]
        #[ValidContentTreeDepth(max: 3)]
        public array $contentTree = [],
    ) {
    }
}
