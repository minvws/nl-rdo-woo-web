<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Uploader\WooDecision\Process;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Post(
            name: 'api_uploader_woo_decision_process',
            uriTemplate: '/balie/api/uploader/woo-decision/{dossierId}/process',
            security: "is_granted('AuthMatrix.dossier.update', object)",
            input: false,
            output: false,
            stateless: false,
            provider: ProcessUploadsProvider::class,
            processor: ProcessUploadsProcessor::class,
            exceptionToStatus: [
                DocumentFileSetException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            openapi: new Operation(
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_TAG => ['admin'],
                ],
            ),
        ),
    ],
)]
final class ProcessUploadsDto
{
    #[ApiProperty(identifier: true)]
    public Uuid $dossierId;
}
