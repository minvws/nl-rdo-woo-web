<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticObjectField;

readonly class SubjectMapper
{
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
                ElasticField::SUMMARY->value => $subject->getLandingPageDescription(),
                ElasticField::STATUS->value => $subject->getLandingPageStatus()->value,
                ElasticObjectField::SUBJECT->value => [
                    ElasticField::ID->value => $subject->getId(),
                    ElasticField::NAME->value => $subject->getName(),
                ],
                ElasticField::ORGANISATION_IDS->value => [$subject->getOrganisation()->getId()],
                ElasticField::HAS_VISIBLE_CONTENT_TREE->value => $subject->hasVisibleLandingPageContentTree(),
            ],
        );
    }
}
