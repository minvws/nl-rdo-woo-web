<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin\Publication\WooDecision;

use Mockery;
use Shared\Controller\Admin\Upload\WooDecisionDocumentUploadController;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\Publication\WooDecision\UploadDocuments;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class UploadDocumentsTest extends UnitTestCase
{
    public function testGetUploadEndpoint(): void
    {
        $dossierId = Uuid::fromString($this->getFaker()->uuid());
        $uploadEndpoint = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(WooDecisionDocumentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $dossierId])
            ->andReturn($uploadEndpoint);

        $uploadDocuments = new UploadDocuments($urlGenerator);
        $uploadDocuments->dossierId = $dossierId;

        self::assertSame($uploadEndpoint, $uploadDocuments->getUploadEndpoint());
    }
}
