<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use Shared\ValueObject\Equatable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

class ImmutableValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, Immutable::class);

        $entity = $this->context->getObject();
        Assert::object($entity);

        if ($this->isNewEntity($entity)) {
            return;
        }

        $propertyName = $this->context->getPropertyName();
        Assert::string($propertyName);

        $originalData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity);
        Assert::isArray($originalData);
        Assert::keyExists($originalData, $propertyName);

        if ($this->isEqual($originalData[$propertyName], $value)) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setCode(Immutable::ERROR_CODE)
            ->addViolation();
    }

    private function isEqual(mixed $old, mixed $new): bool
    {
        if ($old instanceof Equatable && $new instanceof Equatable && $old::class === $new::class) {
            return $old->equalTo($new);
        }

        // depending on how the entity was loaded, either side may be the enum instance rather than its scalar value
        if ($old instanceof BackedEnum) {
            $old = $old->value;
        }
        if ($new instanceof BackedEnum) {
            $new = $new->value;
        }

        return $old === $new;
    }

    private function isNewEntity(object $entity): bool
    {
        if (! $this->entityManager->contains($entity)) {
            return true;
        }

        return $this->entityManager->getUnitOfWork()->isScheduledForInsert($entity);
    }
}
