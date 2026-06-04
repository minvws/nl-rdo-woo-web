<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant\Uploads\Attachment;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class CovenantUploadAttachmentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $attachmentExternalId,
    ): CovenantUploadAttachmentRequestDto {
        return new CovenantUploadAttachmentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
            ExternalId::create($attachmentExternalId),
        );
    }
}
