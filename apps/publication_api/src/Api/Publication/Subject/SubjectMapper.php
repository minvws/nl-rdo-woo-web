<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Subject;

use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;

use function array_map;
use function array_values;

class SubjectMapper
{
    /**
     * @param array<array-key,Subject> $subjects
     *
     * @return array<array-key,SubjectDto>
     */
    public static function fromEntities(array $subjects): array
    {
        return array_values(array_map(self::fromEntity(...), $subjects));
    }

    public static function fromEntity(Subject $subject): SubjectDto
    {
        return new SubjectDto(
            $subject->getId(),
            OrganisationReferenceDto::fromEntity($subject->getOrganisation()),
            $subject->getName(),
        );
    }

    public static function fromCreateDto(SubjectCreateDto $subjectCreateDto, Organisation $organisation): Subject
    {
        $subject = new Subject();
        $subject->setName($subjectCreateDto->name);
        $subject->setOrganisation($organisation);

        return $subject;
    }

    public static function fromUpdateDto(Subject $subject, SubjectUpdateDto $subjectUpdateDto): Subject
    {
        $subject->setName($subjectUpdateDto->name);

        return $subject;
    }
}
