<?php

declare(strict_types=1);

namespace Shared\Validator\HasOneAttachmentOfTypes;

use Doctrine\Common\Collections\Collection;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

use function in_array;

class HasOneAttachmentOfTypesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, HasOneAttachmentOfTypes::class);
        Assert::object($value);

        $accessor = PropertyAccess::createPropertyAccessor();
        $attachments = $accessor->getValue($value, $constraint->property);

        Assert::isInstanceOf($attachments, Collection::class);

        foreach ($attachments as $attachment) {
            Assert::isInstanceOf($attachment, AbstractAttachment::class);

            if (in_array($attachment->getType(), $constraint->types, true)) {
                return;
            }
        }

        if ($constraint->errorPaths === []) {
            $this->context->buildViolation($constraint->message)->addViolation();

            return;
        }

        foreach ($constraint->errorPaths as $errorPath) {
            $this->context->buildViolation($constraint->message)
                ->atPath($errorPath)
                ->addViolation();
        }
    }
}
