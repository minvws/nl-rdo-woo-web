<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin\Publication;

use Mockery;
use Shared\Controller\Admin\Upload\AttachmentUploadController;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\Publication\ManageAttachments;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ManageAttachmentsTest extends UnitTestCase
{
    public function testGetUploadEndpoint(): void
    {
        $dossierId = $this->getFaker()->uuid();
        $uploadEndpoint = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(AttachmentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $dossierId])
            ->andReturn($uploadEndpoint);

        $manageAttachments = new ManageAttachments($urlGenerator);
        $manageAttachments->dossierId = $dossierId;

        self::assertSame($uploadEndpoint, $manageAttachments->getUploadEndpoint());
    }
}
