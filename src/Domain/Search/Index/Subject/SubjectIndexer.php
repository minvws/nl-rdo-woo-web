<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Elastic\ElasticService;

readonly class SubjectIndexer
{
    public function __construct(
        private ElasticService $elasticService,
        private SubjectMapper $subjectMapper,
    ) {
    }

    public function index(Subject $subject): void
    {
        $this->elasticService->updateDocument(
            $this->subjectMapper->map($subject),
        );
    }
}
