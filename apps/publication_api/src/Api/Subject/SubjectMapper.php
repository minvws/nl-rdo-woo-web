<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use PublicationApi\Api\Organisation\OrganisationMapper;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class SubjectMapper
{
    /**
     * @param array<array-key,Subject> $subjects
     *
     * @return list<SubjectResponseDto>
     */
    public static function fromEntities(array $subjects): array
    {
        return array_values(array_map(self::fromEntity(...), $subjects));
    }

    public static function fromEntity(Subject $subject): SubjectResponseDto
    {
        return new SubjectResponseDto(
            $subject->getId(),
            $subject->getName(),
        );
    }

    public static function fromNullableEntity(?Subject $subject): ?SubjectResponseDto
    {
        return $subject !== null ? self::fromEntity($subject) : null;
    }

    /**
     * @param array<array-key,Subject> $subjects
     *
     * @return list<SubjectDetailResponseDto>
     */
    public static function fromEntitiesWithDetail(
        array $subjects,
        ?SubjectPreviewUrlGenerator $previewUrlGenerator = null,
    ): array {
        return array_values(array_map(
            static function (Subject $subject) use ($previewUrlGenerator): SubjectDetailResponseDto {
                return self::fromEntityWithDetail($subject, $previewUrlGenerator);
            },
            $subjects,
        ));
    }

    public static function fromEntityWithDetail(
        Subject $subject,
        ?SubjectPreviewUrlGenerator $previewUrlGenerator = null,
    ): SubjectDetailResponseDto {
        return new SubjectDetailResponseDto(
            $subject->getId(),
            OrganisationMapper::fromEntity($subject->getOrganisation()),
            $subject->getName(),
            self::mapLandingPage($subject, $previewUrlGenerator),
        );
    }

    public static function fromCreateDto(SubjectCreateDto $subjectCreateDto, Organisation $organisation): Subject
    {
        $subject = new Subject();
        $subject->setName($subjectCreateDto->name);
        $subject->setOrganisation($organisation);
        self::applyLandingPage($subject, $subjectCreateDto->landingPage);

        return $subject;
    }

    public static function fromUpdateDto(Subject $subject, SubjectUpdateDto $subjectUpdateDto): Subject
    {
        $subject->setName($subjectUpdateDto->name);
        self::applyLandingPage($subject, $subjectUpdateDto->landingPage);

        return $subject;
    }

    private static function applyLandingPage(
        Subject $subject,
        ?SubjectLandingPageInputDto $landingPage,
    ): void {
        if ($landingPage === null) {
            return;
        }

        $subject->setLandingPage(
            $landingPage->slug,
            $landingPage->title,
            $landingPage->description,
            $landingPage->status,
            $landingPage->contentTree,
            $landingPage->contentTreeStatus,
        );
    }

    private static function mapLandingPage(
        Subject $subject,
        ?SubjectPreviewUrlGenerator $previewUrlGenerator,
    ): ?SubjectLandingPageOutputDto {
        if (! $subject->hasLandingPageContent()) {
            return null;
        }

        $slug = $subject->getLandingPageSlug();
        $title = $subject->getLandingPageTitle();
        $description = $subject->getLandingPageDescription();
        Assert::isInstanceOf($slug, LandingPageSlug::class);
        Assert::isInstanceOf($title, LandingPageTitle::class);
        Assert::string($description);

        $contentTree = $subject->getLandingPageContentTree() ?? new SubjectContentTree(
            title: '',
            intro: '',
            children: [],
            outro: '',
        );

        return new SubjectLandingPageOutputDto(
            $subject->getLandingPageStatus(),
            (string) $slug,
            $title->toString(),
            $description,
            $subject->getLandingPageContentTreeStatus(),
            $contentTree,
            $previewUrlGenerator?->generatePreviewUrl($subject),
        );
    }
}
