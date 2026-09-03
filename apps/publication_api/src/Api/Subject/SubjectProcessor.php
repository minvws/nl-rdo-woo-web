<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Organisation\OrganisationResolverInterface;
use PublicationApi\Domain\Exception\ResourceInUseException;
use PublicationApi\Domain\Validator\EntityValidator;
use PublicationApi\FeatureFlag\SubjectLandingPageGuard;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Publication\Subject\SubjectService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<SubjectCreateDto|SubjectUpdateDto,?SubjectDetailResponse>
 */
final readonly class SubjectProcessor implements ProcessorInterface
{
    public function __construct(
        private OrganisationResolverInterface $organisationResolver,
        private SubjectRepository $subjectRepository,
        private SubjectService $subjectService,
        private EntityValidator $validator,
        private SubjectPreviewUrlGenerator $subjectPreviewUrlGenerator,
        private SubjectLandingPageGuard $subjectLandingPageGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SubjectDetailResponse
    {
        unset($context);

        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, SubjectCreateDto::class);
            $subject = $this->create($organisation, $data);

            return SubjectMapper::fromEntityWithDetail($subject, $this->subjectPreviewUrlGenerator);
        }

        Assert::string($uriVariables['subjectId']);
        $subject = $this->subjectRepository->findByOrganisationAndId(
            $organisation,
            Uuid::fromString($uriVariables['subjectId']),
        );
        Assert::isInstanceOf($subject, Subject::class);

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, SubjectUpdateDto::class);
            $this->update($subject, $data);

            return SubjectMapper::fromEntityWithDetail($subject, $this->subjectPreviewUrlGenerator);
        }

        if ($operation instanceof Delete) {
            $this->delete($subject);
        }

        return null;
    }

    private function create(Organisation $organisation, SubjectCreateDto $subjectCreateDto): Subject
    {
        if ($subjectCreateDto->landingPage !== null) {
            $this->subjectLandingPageGuard->assertEnabled();
        }

        $subject = SubjectMapper::fromCreateDto($subjectCreateDto, $organisation);

        $this->validator->throwExceptionIfNotValid($subject);

        $this->subjectService->saveNew($subject);

        return $subject;
    }

    private function update(Subject $subject, SubjectUpdateDto $subjectUpdateDto): Subject
    {
        if ($subjectUpdateDto->landingPage !== null) {
            $this->subjectLandingPageGuard->assertEnabled();
        }

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
