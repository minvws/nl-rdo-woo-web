<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Subject;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<SubjectCreateDto|SubjectUpdateDto,?SubjectDto>
 */
final readonly class SubjectProcessor implements ProcessorInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private SubjectRepository $subjectRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SubjectDto
    {
        unset($context);

        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        Assert::isInstanceOf($organisation, Organisation::class);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, SubjectCreateDto::class);
            $subject = $this->create($organisation, $data);

            return SubjectMapper::fromEntity($subject);
        }

        $subject = $this->subjectRepository->find($uriVariables['subjectId']);
        Assert::isInstanceOf($subject, Subject::class);

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, SubjectUpdateDto::class);
            $this->update($subject, $data);

            return SubjectMapper::fromEntity($subject);
        }

        if ($operation instanceof Delete) {
            $this->delete($subject);
        }

        return null;
    }

    private function create(Organisation $organisation, SubjectCreateDto $subjectCreateDto): Subject
    {
        $subject = SubjectMapper::fromCreateDto($subjectCreateDto, $organisation);
        $this->subjectRepository->save($subject, true);

        return $subject;
    }

    private function update(Subject $subject, SubjectUpdateDto $subjectUpdateDto): Subject
    {
        $subject = SubjectMapper::fromUpdateDto($subject, $subjectUpdateDto);
        $this->subjectRepository->save($subject, true);

        return $subject;
    }

    private function delete(Subject $subject): void
    {
        $this->subjectRepository->remove($subject, true);
    }
}
