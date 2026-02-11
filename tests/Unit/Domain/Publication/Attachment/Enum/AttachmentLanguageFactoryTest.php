<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Enum;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguageFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentLanguageFactoryTest extends UnitTestCase
{
    public function testMakeAsArray(): void
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentLanguageFactory($translator);
        $result = $factory->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }
}
