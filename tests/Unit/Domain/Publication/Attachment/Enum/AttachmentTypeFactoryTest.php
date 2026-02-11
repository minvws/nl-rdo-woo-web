<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Enum;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Enum\AttachmentTypeFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentTypeFactoryTest extends UnitTestCase
{
    public function testItCanBeInitialized(): void
    {
        $translator = Mockery::mock(TranslatorInterface::class);

        $factory = new AttachmentTypeFactory($translator);

        $this->assertInstanceOf(AttachmentTypeFactory::class, $factory);
    }

    public function testMake(): void
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->make();

        $this->assertMatchesObjectSnapshot($result);
    }

    public function testMakeAsArray(): void
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }

    public function testMakeAsArrayWithAllowedTypes(): void
    {
        $translator = Mockery::mock(TranslatorInterface::class);
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
