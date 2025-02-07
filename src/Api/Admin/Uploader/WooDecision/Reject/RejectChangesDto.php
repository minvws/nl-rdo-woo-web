<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Reject;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domain\Publication\Dossier\Type\WooDecision\Exception\DocumentFileSetException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

#[ApiResource]
#[Post(
    name: 'api_uploader_woo_decision_reject_changes',
    uriTemplate: '/uploader/woo-decision/{dossierId}/reject-changes',
    security: "is_granted('AuthMatrix.dossier.update', object)",
    input: false,
    output: false,
    stateless: false,
    provider: RejectChangesProvider::class,
    processor: RejectChangesProcessor::class,
    exceptionToStatus: [
        DocumentFileSetException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
    ],
)]
final class RejectChangesDto
{
    #[ApiProperty(identifier: true)]
    public Uuid $dossierId;
}
