<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\UniqueDossierNumber;
use Shared\Validator\UniqueDossierNumberValidator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueDossierNumberValidatorTest extends UnitTestCase
{
    public function testNoViolationForEmptyValue(): void
    {
        $repository = Mockery::mock(DossierRepository::class);
        $context = Mockery::mock(ExecutionContextInterface::class);

        $validator = new UniqueDossierNumberValidator($repository);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('', new UniqueDossierNumber(documentPrefix: 'pfx'));
    }

    public function testNoViolationWhenNoDossierFound(): void
    {
        $repository = Mockery::mock(DossierRepository::class);
        $context = Mockery::mock(ExecutionContextInterface::class);

        $repository->expects('findOneBy')
            ->with(['documentPrefix' => 'pfx', 'dossierNumber' => 'ref-001'])
            ->andReturnNull();

        $validator = new UniqueDossierNumberValidator($repository);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('ref-001', new UniqueDossierNumber(documentPrefix: 'pfx'));
    }

    public function testNoViolationWhenMatchIsExcludedId(): void
    {
        $excludeId = Uuid::v6();

        $existing = Mockery::mock(AbstractDossier::class);
        $existing->expects('getId')->andReturn($excludeId);

        $repository = Mockery::mock(DossierRepository::class);
        $context = Mockery::mock(ExecutionContextInterface::class);

        $repository->expects('findOneBy')
            ->with(['documentPrefix' => 'pfx', 'dossierNumber' => 'ref-001'])
            ->andReturn($existing);

        $validator = new UniqueDossierNumberValidator($repository);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('ref-001', new UniqueDossierNumber(documentPrefix: 'pfx', excludeId: $excludeId));
    }

    public function testViolationWhenDossierNumberAlreadyTaken(): void
    {
        $existing = Mockery::mock(AbstractDossier::class);
        $existing->expects('getId')->andReturn(Uuid::v6());

        $repository = Mockery::mock(DossierRepository::class);
        $context = Mockery::mock(ExecutionContextInterface::class);

        $repository->expects('findOneBy')
            ->with(['documentPrefix' => 'pfx', 'dossierNumber' => 'ref-001'])
            ->andReturn($existing);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $context->expects('buildViolation')->with('dossier.dossier_number_not_unique')->andReturn($builder);
        $builder->expects('addViolation');

        $validator = new UniqueDossierNumberValidator($repository);
        $validator->initialize($context);

        $validator->validate('ref-001', new UniqueDossierNumber(documentPrefix: 'pfx', excludeId: Uuid::v6()));
    }
}
