<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Enum;

use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Enum\AttachmentTypeBranch;
use Shared\Domain\Publication\Attachment\Exception\AttachmentTypeBranchException;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentTypeBranchTest extends UnitTestCase
{
    public function testItCanBeInitialized(): void
    {
        $branch = \Mockery::mock(AttachmentTypeBranch::class);

        $dto = new AttachmentTypeBranch(name: 'test', branch: $branch);

        $this->assertInstanceOf(AttachmentTypeBranch::class, $dto);
    }

    public function testHasBranchAndNoAttachmentTypes(): void
    {
        $child = \Mockery::mock(AttachmentTypeBranch::class);
        $dto = new AttachmentTypeBranch(
            name: 'test',
            branch: $child,
        );

        $this->assertTrue($dto->hasBranch());
        $this->assertFalse($dto->hasAttachmentTypes());
    }

    public function testHasAttachmentTypesAndNoBranch(): void
    {
        $dto = new AttachmentTypeBranch(
            name: 'test',
            attachmentTypes: [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE],
        );

        $this->assertFalse($dto->hasBranch());
        $this->assertTrue($dto->hasAttachmentTypes());
    }

    public function testSettingABranchOrNonEmptyAttachmentTypesIsRequired(): void
    {
        $this->expectExceptionObject(AttachmentTypeBranchException::mandatoryArguments());

        new AttachmentTypeBranch(name: 'test');
    }

    public function testToArrayWithABranchAndWithoutAttachmentTypes(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $branch = \Mockery::mock(AttachmentTypeBranch::class);
        $branch->shouldReceive('toArray')->with($translator)->once()->andReturn(['placeholder']);

        $dto = new AttachmentTypeBranch(
            name: 'test',
            branch: $branch,
        );

        $result = $dto->toArray($translator);

        $this->assertSame([
            'type' => 'AttachmentTypeBranch',
            'label' => 'test',
            'subbranch' => ['placeholder'],
            'attachmentTypes' => [],
        ], $result);
    }

    public function testToArrayWithoutABranchAndWithAttachmentTypes(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0)->byDefault();
        $translator->shouldReceive('trans')->with('advice', [], AttachmentType::TRANS_DOMAIN, null)->once()->andReturnArg(0);
        $translator->shouldReceive('trans')->with('request_for_advice', [], AttachmentType::TRANS_DOMAIN, null)->once()->andReturnArg(0);

        $attachmentTypes = [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE];

        $dto = new AttachmentTypeBranch(
            name: 'test',
            attachmentTypes: $attachmentTypes,
        );

        $result = $dto->toArray($translator);

        $this->assertSame([
            'type' => 'AttachmentTypeBranch',
            'label' => 'test',
            'subbranch' => null,
            'attachmentTypes' => [
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_d506b718',
                    'label' => 'advice',
                ],
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_a40458df',
                    'label' => 'request_for_advice',
                ],
            ],
        ], $result);
    }

    public function testToArrayWithoutABranchAndWithAttachmentTypesWithoutTranslator(): void
    {
        $attachmentTypes = [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE];

        $dto = new AttachmentTypeBranch(
            name: 'test',
            attachmentTypes: $attachmentTypes,
        );

        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $result = $dto->toArray($translator);

        $this->assertSame([
            'type' => 'AttachmentTypeBranch',
            'label' => 'test',
            'subbranch' => null,
            'attachmentTypes' => [
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_d506b718',
                    'label' => 'advice',
                ],
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_a40458df',
                    'label' => 'request_for_advice',
                ],
            ],
        ], $result);
    }

    public function testToArrayWithABranchAndWithAttachmentTypes(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0)->byDefault();
        $translator->shouldReceive('trans')->with('advice', [], AttachmentType::TRANS_DOMAIN, null)->once()->andReturnArg(0);
        $translator->shouldReceive('trans')->with('request_for_advice', [], AttachmentType::TRANS_DOMAIN, null)->once()->andReturnArg(0);

        $attachmentTypes = [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE];

        $branch = \Mockery::mock(AttachmentTypeBranch::class);
        $branch->shouldReceive('toArray')->with($translator)->once()->andReturn(['placeholder']);

        $dto = new AttachmentTypeBranch(
            name: 'test',
            branch: $branch,
            attachmentTypes: $attachmentTypes,
        );

        $result = $dto->toArray($translator);

        $this->assertSame([
            'type' => 'AttachmentTypeBranch',
            'label' => 'test',
            'subbranch' => ['placeholder'],
            'attachmentTypes' => [
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_d506b718',
                    'label' => 'advice',
                ],
                [
                    'type' => 'AttachmentType',
                    'value' => 'c_a40458df',
                    'label' => 'request_for_advice',
                ],
            ],
        ], $result);
    }

    public function testFilter(): void
    {
        $allowedTypes = [
            AttachmentType::ADVICE,
            AttachmentType::ANNUAL_PLAN,
            AttachmentType::SPEECH,
        ];

        $mockedbranch = \Mockery::mock(AttachmentTypeBranch::class);
        $mockedbranch->shouldReceive('filter')->with($allowedTypes)->once()->andReturn($mockedbranch);

        $dto = new AttachmentTypeBranch(
            name: 'test',
            branch: $mockedbranch,
            attachmentTypes: [
                AttachmentType::ANNUAL_PLAN,
                AttachmentType::CONCESSION,
                AttachmentType::ADVICE,
                AttachmentType::TERM_AGENDA,
            ],
        );

        $result = $dto->filter($allowedTypes);

        $this->assertNotNull($result);
        $this->assertSame([AttachmentType::ANNUAL_PLAN, AttachmentType::ADVICE], $result->attachmentTypes);
    }

    public function testFilterWithoutPassingAnyAllowedTypes(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $dto = new AttachmentTypeBranch(
            name: 'test',
            attachmentTypes: $expectedAttachmentTypes = [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE],
        );
        $result = $dto->filter();

        $this->assertNotNull($result);
        $this->assertSame($expectedAttachmentTypes, $result->attachmentTypes);
    }

    public function testFilterPassingEmptyAllowedTypesArray(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $dto = new AttachmentTypeBranch(
            name: 'test',
            attachmentTypes: [AttachmentType::ADVICE, AttachmentType::REQUEST_FOR_ADVICE],
        );
        $result = $dto->filter([]);

        $this->assertNull($result);
    }
}
