<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Validator\Immutable;
use Shared\Domain\Publication\Dossier\Validator\ImmutableValidator;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ImmutableValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class ImmutableValidatorTest extends ConstraintValidatorTestCase
{
    use MockeryPHPUnitIntegration;

    private EntityManagerInterface&MockInterface $entityManager;

    protected function createValidator(): ImmutableValidator
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        return new ImmutableValidator($this->entityManager);
    }

    public function testNoViolationForUnmanagedEntity(): void
    {
        $entity = $this->createEntity();
        $this->setProperty($entity, 'field');
        $this->entityManager->expects('contains')->with($entity)->andReturnFalse();

        $this->validate('new value', new Immutable());

        self::assertNoViolation();
    }

    public function testNoViolationForEntityScheduledForInsert(): void
    {
        $entity = $this->createEntity();
        $this->setProperty($entity, 'field');
        $this->entityManager->expects('contains')->with($entity)->andReturnTrue();
        $this->entityManager->expects('getUnitOfWork->isScheduledForInsert')->with($entity)->andReturnTrue();

        $this->validate('new value', new Immutable());

        self::assertNoViolation();
    }

    #[DataProvider('unchangedValueDataProvider')]
    public function testNoViolationForUnchangedValue(mixed $originalValue, mixed $value): void
    {
        $this->givenManagedEntityWithOriginalValue($originalValue);

        $this->validate($value, new Immutable());

        self::assertNoViolation();
    }

    /**
     * @return array<string, array{originalValue: mixed, value: mixed}>
     */
    public static function unchangedValueDataProvider(): array
    {
        return [
            'equal equatable in another instance' => [
                'originalValue' => DossierTitle::create('Some title'),
                'value' => DossierTitle::create('Some title'),
            ],
            'same scalar' => [
                'originalValue' => 'value',
                'value' => 'value',
            ],
            'enum and its scalar value' => [
                'originalValue' => DossierStatus::PUBLISHED,
                'value' => DossierStatus::PUBLISHED->value,
            ],
            'scalar value and its enum' => [
                'originalValue' => DossierStatus::PUBLISHED->value,
                'value' => DossierStatus::PUBLISHED,
            ],
        ];
    }

    #[DataProvider('changedValueDataProvider')]
    public function testViolationForChangedValue(mixed $originalValue, mixed $value): void
    {
        $this->givenManagedEntityWithOriginalValue($originalValue);

        $this->validate($value, new Immutable());

        $this->buildViolation('validation.immutable')
            ->setCode(Immutable::ERROR_CODE)
            ->assertRaised();
    }

    /**
     * @return array<string, array{originalValue: mixed, value: mixed}>
     */
    public static function changedValueDataProvider(): array
    {
        return [
            'different equatable' => [
                'originalValue' => DossierTitle::create('Some title'),
                'value' => DossierTitle::create('Other title'),
            ],
            'equatable replaced by null' => [
                'originalValue' => PlainDate::create('2025-01-01'),
                'value' => null,
            ],
            'equatable replaced by its string value' => [
                'originalValue' => PlainDate::create('2025-01-01'),
                'value' => '2025-01-01',
            ],
            'different scalar' => [
                'originalValue' => 'value',
                'value' => 'other value',
            ],
            'different enum' => [
                'originalValue' => DossierStatus::PUBLISHED,
                'value' => DossierStatus::CONCEPT,
            ],
        ];
    }

    private function createEntity(): object
    {
        return new class {
            public mixed $field = null;
        };
    }

    private function givenManagedEntityWithOriginalValue(mixed $originalValue): void
    {
        $entity = $this->createEntity();
        $this->setProperty($entity, 'field');

        $unitOfWork = Mockery::mock(UnitOfWork::class);
        $unitOfWork->expects('isScheduledForInsert')->with($entity)->andReturnFalse();
        $unitOfWork->expects('getOriginalEntityData')->with($entity)->andReturn(['field' => $originalValue]);

        $this->entityManager->expects('contains')->with($entity)->andReturnTrue();
        $this->entityManager->expects('getUnitOfWork')->twice()->andReturn($unitOfWork);
    }
}
