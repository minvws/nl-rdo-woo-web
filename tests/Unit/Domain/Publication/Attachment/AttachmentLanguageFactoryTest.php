<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AttachmentLanguageFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentLanguageFactoryTest extends UnitTestCase
{
    public function testMakeAsArray(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $factory = new AttachmentLanguageFactory($translator);
        $result = $factory->makeAsArray();

        $this->assertMatchesJsonSnapshot($result);
    }
}
