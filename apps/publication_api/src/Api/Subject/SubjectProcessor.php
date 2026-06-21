<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Domain\Exception\ResourceInUseException;
use PublicationApi\Domain\Validator\EntityValidator;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Publication\Subject\SubjectService;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<SubjectCreateDto|SubjectUpdateDto,?SubjectResponse>
 */
final readonly class SubjectProcessor implements ProcessorInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private SubjectRepository $subjectRepository,
        private SubjectService $subjectService,
        private EntityValidator $validator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SubjectDetailResponse
    {
        unset($context);

        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        Assert::isInstanceOf($organisation, Organisation::class);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, SubjectCreateDto::class);
            $subject = $this->create($organisation, $data);

            return SubjectMapper::fromEntityWithDetail($subject);
        }

        $subject = $this->subjectRepository->find($uriVariables['subjectId']);
        Assert::isInstanceOf($subject, Subject::class);

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, SubjectUpdateDto::class);
            $this->update($subject, $data);

            return SubjectMapper::fromEntityWithDetail($subject);
        }

        if ($operation instanceof Delete) {
            $this->delete($subject);
        }

        return null;
    }

    private function create(Organisation $organisation, SubjectCreateDto $subjectCreateDto): Subject
    {
        $subject = SubjectMapper::fromCreateDto($subjectCreateDto, $organisation);

        $this->validator->throwExceptionIfNotValid($subject);

        $this->subjectService->saveNew($subject);

        return $subject;
    }

    private function update(Subject $subject, SubjectUpdateDto $subjectUpdateDto): Subject
    {
        $subject = SubjectMapper::fromUpdateDto($subject, $subjectUpdateDto);

        $this->validator->throwExceptionIfNotValid($subject);

        $this->subjectService->save($subject);

        return $subject;
    }

    private function delete(Subject $subject): void
    {
        if ($this->subjectRepository->isInUse($subject)) {
            throw new ResourceInUseException();
        }

        $this->subjectRepository->remove($subject, true);
    }
}
