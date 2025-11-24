<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Api\Admin\Attachment;

use Mockery\MockInterface;
use Shared\Api\Admin\Attachment\AttachmentDtoFactory;
use Shared\Api\Admin\WooDecisionAttachment\WooDecisionAttachmentDto;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class AttachmentDtoFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private AttachmentDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $this->factory = new AttachmentDtoFactory($this->urlGenerator);
    }

    public function testMake(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getDocumentPrefix')->andReturn($prefix = 'FOO');
        $wooDecision->shouldReceive('getDossierNr')->andReturn($dossierNr = 'BAR-123');
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));

        $attachment = \Mockery::mock(WooDecisionAttachment::class);
        $attachment->shouldReceive('getDossier')->andReturn($wooDecision);
        $attachment->shouldReceive('getId')->andReturn($attachmentId = Uuid::fromString('00000000-0000-0000-0000-000000000002'));
        $attachment->shouldReceive('getFileInfo->getName')->andReturn('foo.pdf');
        $attachment->shouldReceive('getFileInfo->getMimeType')->andReturn('application/pdf');
        $attachment->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable('2024-01-14 09:10:13'));
        $attachment->shouldReceive('getType')->andReturn(AttachmentType::ANNUAL_REPORT);
        $attachment->shouldReceive('getFileInfo->getSize')->andReturn(123);
        $attachment->shouldReceive('getInternalReference')->andReturn('internal ref X');
        $attachment->shouldReceive('getLanguage')->andReturn(AttachmentLanguage::DUTCH);
        $attachment->shouldReceive('getGrounds')->andReturn(['ground A', 'ground B']);

        $this->urlGenerator->shouldReceive('generate')->with(
            'app_admin_dossier_attachment_withdraw',
            [
                'prefix' => $prefix,
                'dossierId' => $dossierNr,
                'attachmentId' => $attachmentId,
            ]
        )->andReturn('/foo/bar');

        $this->assertMatchesSnapshot(
            $this->factory->make(WooDecisionAttachmentDto::class, $attachment),
        );
    }
}
