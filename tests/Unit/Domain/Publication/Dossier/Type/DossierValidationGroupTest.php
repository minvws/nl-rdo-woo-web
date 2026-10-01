<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Tests\Unit\UnitTestCase;

use function array_filter;
use function array_values;
use function in_array;
use function sprintf;

final class DossierValidationGroupTest extends UnitTestCase
{
    public function testGetValidationGroupsForStepName(): void
    {
        $result = [];
        foreach (StepName::cases() as $stepName) {
            $result[$stepName->value] = DossierValidationGroup::getValidationGroupsForStepName($stepName);
        }

        $this->assertMatchesSnapshot($result);
    }

    public function testGetForWorkflowTransitions(): void
    {
        $result = [];
        foreach (DossierStatusTransition::cases() as $transition) {
            $result[$transition->value] = DossierValidationGroup::getForWorkflowTransitions($transition);
        }

        $this->assertMatchesSnapshot($result);
    }

    public function testGetForStatus(): void
    {
        $result = [];
        foreach (DossierStatus::cases() as $dossierStatus) {
            $result[$dossierStatus->value] = DossierValidationGroup::getForStatus($dossierStatus);
        }

        $this->assertMatchesSnapshot($result);
    }

    public function testGetForLinkedDossierStatusesWithoutAnyLinkedDossier(): void
    {
        self::assertNotContains(
            DossierValidationGroup::PUBLICATION_LOCKED,
            DossierValidationGroup::getForLinkedDossierStatuses([]),
        );
    }

    /**
     * @param list<DossierStatus> $dossierStatuses
     */
    #[DataProvider('linkedDossierStatusesProvider')]
    public function testGetForLinkedDossierStatuses(array $dossierStatuses, bool $expectedLocked): void
    {
        $result = DossierValidationGroup::getForLinkedDossierStatuses($dossierStatuses);

        self::assertSame(
            $expectedLocked,
            in_array(DossierValidationGroup::PUBLICATION_LOCKED, $result, true),
        );
        $withoutLock = array_filter(
            $result,
            static fn (DossierValidationGroup $group): bool => $group !== DossierValidationGroup::PUBLICATION_LOCKED,
        );

        self::assertSame(DossierValidationGroup::allNonWorkflowGroups(), array_values($withoutLock));
    }

    /**
     * @return iterable<string,array{list<DossierStatus>,bool}>
     */
    public static function linkedDossierStatusesProvider(): iterable
    {
        foreach (DossierStatus::cases() as $dossierStatus) {
            $isNonConcept = in_array($dossierStatus, DossierStatus::nonConceptCases(), true);

            yield sprintf('single %s', $dossierStatus->value) => [[$dossierStatus], $isNonConcept];

            foreach (DossierStatus::cases() as $otherDossierStatus) {
                yield sprintf('%s and %s', $dossierStatus->value, $otherDossierStatus->value) => [
                    [$dossierStatus, $otherDossierStatus],
                    $isNonConcept || in_array($otherDossierStatus, DossierStatus::nonConceptCases(), true),
                ];
            }
        }
    }

    public function testAllNonWorkflowGroups(): void
    {
        $result = DossierValidationGroup::allNonWorkflowGroups();

        $this->assertMatchesSnapshot($result);
    }
}
