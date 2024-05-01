<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Attachment\AttachmentTypeFactory;
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

    public function testMakeAsArrayWithExclude(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentTypeFactory($translator);
        $result = $factory->makeAsArray(AttachmentType::COVENANT);

        $this->assertMatchesJsonSnapshot($result);
    }
}
