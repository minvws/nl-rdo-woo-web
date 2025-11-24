<?php

declare(strict_types=1);

namespace Shared\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

class EntityExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, EntityExists::class);

        if ($value === null) {
            return;
        }

        $repository = $this->entityManager->getRepository($constraint->entityClass);
        $entity = $repository->findOneBy([$constraint->field => $value]);

        if ($entity instanceof $constraint->entityClass) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ name }}', $constraint->name)
            ->setCode(EntityExists::ENTITY_EXISTS_ERROR)
            ->addViolation();
    }
}
