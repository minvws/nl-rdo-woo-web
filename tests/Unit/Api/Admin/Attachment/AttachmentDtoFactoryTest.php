<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Api\Admin\Attachment;

use Admin\Api\Admin\Attachment\AttachmentDtoFactory;
use Admin\Api\Admin\WooDecisionAttachment\WooDecisionAttachmentDto;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class AttachmentDtoFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private AttachmentDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);

        $this->factory = new AttachmentDtoFactory($this->urlGenerator);
    }

    public function testMake(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getDocumentPrefix')->andReturn($prefix = 'FOO');
        $wooDecision->expects('getDossierNr')->andReturn($dossierNr = 'BAR-123');
        $wooDecision->expects('getId')->andReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));

        $attachment = Mockery::mock(WooDecisionAttachment::class);
        $attachment->expects('getDossier')->times(3)->andReturn($wooDecision);
        $attachment->expects('getId')->times(2)->andReturn($attachmentId = Uuid::fromString('00000000-0000-0000-0000-000000000002'));
        $attachment->expects('getFileInfo->getName')->andReturn('foo.pdf');
        $attachment->expects('getFileInfo->getMimeType')->andReturn('application/pdf');
        $attachment->expects('getFormalDate')->andReturn(PlainDate::create('2024-01-14'));
        $attachment->expects('getType')->andReturn(AttachmentType::ANNUAL_REPORT);
        $attachment->expects('getFileInfo->getSize')->andReturn(123);
        $attachment->expects('getInternalReference')->andReturn('internal ref X');
        $attachment->expects('getLanguage')->andReturn(AttachmentLanguage::NLD);
        $attachment->expects('getGrounds')->andReturn(['ground A', 'ground B']);

        $this->urlGenerator->expects('generate')
            ->with('app_admin_dossier_attachment_withdraw', [
                'prefix' => $prefix,
                'dossierId' => $dossierNr,
                'attachmentId' => $attachmentId,
            ])
            ->andReturn('/foo/bar');

        $this->assertMatchesSnapshot(
            $this->factory->make(WooDecisionAttachmentDto::class, $attachment),
        );
    }
}
