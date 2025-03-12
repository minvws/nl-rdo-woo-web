<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Enum;

use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\Enum\AttachmentTypeFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentTypeFactoryTest extends UnitTestCase
{
    public function testItCanBeInitialized(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);

        $factory = new AttachmentTypeFactory($translator);

        $this->assertInstanceOf(AttachmentTypeFactory::class, $factory);
    }

    public function testMake(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->make();

        $this->assertMatchesObjectSnapshot($result);
    }

    public function testMakeAsArray(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }

    public function testMakeAsArrayWithAllowedTypes(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->makeAsArray([
            AttachmentType::COVENANT,
            AttachmentType::PARLIAMENTARY_QUESTION_WITH_ANSWER,
            AttachmentType::PARLIAMENTARY_QUESTION_WITHOUT_ANSWER,
            AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_UNDER_ADMINISTRATIVE_ENFORCEMENT,
        ]);

        $this->assertMatchesJsonSnapshot($result);
    }
}
