<?php

declare(strict_types=1);

namespace App\Api\Admin\Department\RemoveLogo;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Uid\Uuid;

#[ApiResource()]
#[Delete(
    name: 'api_uploader_department_remove_logo',
    uriTemplate: '/department/{departmentId}/logo',
    security: "is_granted('AuthMatrix.department.update')",
    input: false,
    output: false,
    stateless: false,
    provider: RemoveLogoProvider::class,
    processor: RemoveLogoProcessor::class,
)]
final class RemoveLogoDto
{
    #[ApiProperty(identifier: true)]
    public Uuid $departmentId;
}
