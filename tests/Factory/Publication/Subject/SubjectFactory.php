<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Subject;

use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Subject>
 */
final class SubjectFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'organisation' => OrganisationFactory::new(),
            'name' => self::faker()->word(),
        ];
    }

    public function withLandingPage(
        SubjectLandingPageStatus $status = SubjectLandingPageStatus::PUBLISHED,
        ?string $slug = null,
    ): self {
        return $this->afterInstantiate(static function (Subject $subject) use ($status, $slug): void {
            $subject->setLandingPage(
                LandingPageSlug::create($slug ?? self::faker()->unique()->slug()),
                LandingPageTitle::create(self::faker()->sentence(3)),
                self::faker()->sentence(),
                $status,
                new SubjectContentTree('', '', [], ''),
            );
        });
    }

    public static function class(): string
    {
        return Subject::class;
    }
}
