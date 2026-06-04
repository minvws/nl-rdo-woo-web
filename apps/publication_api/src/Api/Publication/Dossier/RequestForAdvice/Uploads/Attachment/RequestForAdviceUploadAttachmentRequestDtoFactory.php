<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice\Uploads\Attachment;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class RequestForAdviceUploadAttachmentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $attachmentExternalId,
    ): RequestForAdviceUploadAttachmentRequestDto {
        return new RequestForAdviceUploadAttachmentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
            ExternalId::create($attachmentExternalId),
        );
    }
}
