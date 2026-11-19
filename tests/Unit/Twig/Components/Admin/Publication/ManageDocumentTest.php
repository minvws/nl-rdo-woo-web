<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin\Publication;

use Mockery;
use Shared\Controller\Admin\Upload\MainDocumentUploadController;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\Publication\ManageDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ManageDocumentTest extends UnitTestCase
{
    public function testGetUploadEndpoint(): void
    {
        $dossierId = $this->getFaker()->uuid();
        $uploadEndpoint = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(MainDocumentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $dossierId])
            ->andReturn($uploadEndpoint);

        $manageDocument = new ManageDocument($urlGenerator);
        $manageDocument->dossierId = $dossierId;

        self::assertSame($uploadEndpoint, $manageDocument->getUploadEndpoint());
    }
}
