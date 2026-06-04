<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication\Uploads\Attachment;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class OtherPublicationUploadAttachmentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $attachmentExternalId,
    ): OtherPublicationUploadAttachmentRequestDto {
        return new OtherPublicationUploadAttachmentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
            ExternalId::create($attachmentExternalId),
        );
    }
}
