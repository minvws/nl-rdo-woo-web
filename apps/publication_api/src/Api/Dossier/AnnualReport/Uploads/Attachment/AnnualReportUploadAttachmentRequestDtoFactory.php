<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment;

use GuzzleHttp\Psr7\Utils;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\UuidFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class AnnualReportUploadAttachmentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $attachmentExternalId,
    ): AnnualReportUploadAttachmentRequestDto {
        return new AnnualReportUploadAttachmentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            UuidFactory::create($organisationId),
            ExternalIdFactory::create($dossierExternalId),
            ExternalIdFactory::create($attachmentExternalId),
        );
    }
}
