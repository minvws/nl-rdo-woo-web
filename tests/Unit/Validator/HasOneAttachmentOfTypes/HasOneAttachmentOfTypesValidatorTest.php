<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator\HasOneAttachmentOfTypes;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\HasOneAttachmentOfTypes\HasOneAttachmentOfTypes;
use Shared\Validator\HasOneAttachmentOfTypes\HasOneAttachmentOfTypesValidator;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Webmozart\Assert\InvalidArgumentException;

class HasOneAttachmentOfTypesValidatorTest extends UnitTestCase
{
    public function testThrowsExceptionForWrongConstraint(): void
    {
        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $validator->validate(new stdClass(), Mockery::mock(Constraint::class));
    }

    public function testThrowsExceptionForNonObject(): void
    {
        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $validator->validate('not an object', new HasOneAttachmentOfTypes([AttachmentType::REQUEST_FOR_ADVICE]));
    }

    public function testThrowsExceptionWhenPropertyIsNotACollection(): void
    {
        $object = new stdClass();
        $object->attachments = 'not a collection';

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $validator->validate($object, new HasOneAttachmentOfTypes([AttachmentType::REQUEST_FOR_ADVICE]));
    }

    public function testNoViolationWhenOneOfTheGivenTypesIsPresent(): void
    {
        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getType')->andReturn(AttachmentType::POLICY_DOCUMENT);

        $object = new stdClass();
        $object->attachments = new ArrayCollection([$attachment]);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->never();

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes([
            AttachmentType::REQUEST_FOR_ADVICE,
            AttachmentType::POLICY_DOCUMENT,
        ]));
    }

    public function testViolationWhenNoneOfTheGivenTypesArePresent(): void
    {
        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getType')->andReturn(AttachmentType::OTHER);

        $object = new stdClass();
        $object->attachments = new ArrayCollection([$attachment]);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->with('has_one_attachment_of_types.none')
            ->andReturn($builder);

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes([
            AttachmentType::REQUEST_FOR_ADVICE,
            AttachmentType::POLICY_DOCUMENT,
        ]));
    }

    public function testViolationWhenCollectionIsEmpty(): void
    {
        $object = new stdClass();
        $object->attachments = new ArrayCollection();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->with('has_one_attachment_of_types.none')
            ->andReturn($builder);

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes([AttachmentType::REQUEST_FOR_ADVICE]));
    }

    public function testViolationIsAddedToEachErrorPath(): void
    {
        $object = new stdClass();
        $object->attachments = new ArrayCollection();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('atPath')->with('attachment')->andReturn($builder);
        $builder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->with('has_one_attachment_of_types.none')
            ->andReturn($builder);

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes(
            [AttachmentType::REQUEST_FOR_ADVICE],
            errorPaths: ['attachment'],
        ));
    }

    public function testCustomMessage(): void
    {
        $object = new stdClass();
        $object->attachments = new ArrayCollection();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->with('custom.message')
            ->andReturn($builder);

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes(
            [AttachmentType::REQUEST_FOR_ADVICE],
            message: 'custom.message',
        ));
    }

    public function testCustomProperty(): void
    {
        $attachment = Mockery::mock(AbstractAttachment::class);
        $attachment->expects('getType')->andReturn(AttachmentType::REQUEST_FOR_ADVICE);

        $object = new stdClass();
        $object->customAttachments = new ArrayCollection([$attachment]);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->never();

        $validator = new HasOneAttachmentOfTypesValidator();
        $validator->initialize($context);
        $validator->validate($object, new HasOneAttachmentOfTypes(
            [AttachmentType::REQUEST_FOR_ADVICE],
            property: 'customAttachments',
        ));
    }
}
