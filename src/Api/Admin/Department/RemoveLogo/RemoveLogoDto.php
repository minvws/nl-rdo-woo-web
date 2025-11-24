<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Department\RemoveLogo;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Delete(
            name: 'api_uploader_department_remove_logo',
            uriTemplate: '/balie/api/department/{departmentId}/logo',
            security: "is_granted('AuthMatrix.department.update')",
            input: false,
            output: false,
            stateless: false,
            provider: RemoveLogoProvider::class,
            processor: RemoveLogoProcessor::class,
            openapi: new Operation(
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_TAG => ['admin'],
                ],
            ),
        ),
    ],
)]
final class RemoveLogoDto
{
    #[ApiProperty(identifier: true)]
    public Uuid $departmentId;
}
