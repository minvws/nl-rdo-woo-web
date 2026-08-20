<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\TransitionDossierStatusTrait;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\InvalidArgumentException;

final class TransitionDossierStatusTraitTest extends UnitTestCase
{
    private static function oldState(Transition $transition): DossierStatus
    {
        return (new class {
            use TransitionDossierStatusTrait;

            public function oldState(Transition $transition): DossierStatus
            {
                return $this->getOldState($transition);
            }
        })->oldState($transition);
    }

    private static function newState(Transition $transition): DossierStatus
    {
        return (new class {
            use TransitionDossierStatusTrait;

            public function newState(Transition $transition): DossierStatus
            {
                return $this->getNewState($transition);
            }
        })->newState($transition);
    }

    /**
     * @return iterable<string,array{0:DossierStatus}>
     */
    public static function dossierStatusProvider(): iterable
    {
        foreach (DossierStatus::cases() as $case) {
            yield $case->value => [$case];
        }
    }

    #[DataProvider('dossierStatusProvider')]
    public function testGetOldStateReturnsTheFromStateAsDossierStatus(DossierStatus $status): void
    {
        $transition = new Transition('transition_name', $status->value, DossierStatus::PUBLISHED->value);

        self::assertSame($status, self::oldState($transition));
    }

    #[DataProvider('dossierStatusProvider')]
    public function testGetNewStateReturnsTheToStateAsDossierStatus(DossierStatus $status): void
    {
        $transition = new Transition('transition_name', DossierStatus::CONCEPT->value, $status->value);

        self::assertSame($status, self::newState($transition));
    }

    public function testGetOldStateUsesTheFirstFromStateWhenThereAreMultiple(): void
    {
        $transition = new Transition(
            'transition_name',
            [DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value],
            DossierStatus::PUBLISHED->value,
        );

        self::assertSame(DossierStatus::CONCEPT, self::oldState($transition));
    }

    public function testGetNewStateUsesTheFirstToStateWhenThereAreMultiple(): void
    {
        $transition = new Transition(
            'transition_name',
            DossierStatus::CONCEPT->value,
            [DossierStatus::SCHEDULED->value, DossierStatus::PUBLISHED->value],
        );

        self::assertSame(DossierStatus::SCHEDULED, self::newState($transition));
    }

    public function testGetOldStateThrowsExceptionWhenThereIsNoFromState(): void
    {
        $transition = new Transition('transition_name', [], DossierStatus::PUBLISHED->value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('OldState contained an invalid state');

        self::oldState($transition);
    }

    public function testGetNewStateThrowsExceptionWhenThereIsNoToState(): void
    {
        $transition = new Transition('transition_name', DossierStatus::CONCEPT->value, []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('NewState contained an invalid state');

        self::newState($transition);
    }

    public function testGetOldStateThrowsExceptionWhenTheFromStateIsNotADossierStatus(): void
    {
        $transition = new Transition('transition_name', 'foo', DossierStatus::PUBLISHED->value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('OldState contained an invalid state');

        self::oldState($transition);
    }

    public function testGetNewStateThrowsExceptionWhenTheToStateIsNotADossierStatus(): void
    {
        $transition = new Transition('transition_name', DossierStatus::CONCEPT->value, 'bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('NewState contained an invalid state');

        self::newState($transition);
    }
}
