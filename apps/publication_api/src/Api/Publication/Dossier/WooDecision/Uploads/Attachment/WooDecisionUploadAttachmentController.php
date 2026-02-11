<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class WooDecisionUploadAttachmentController
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $attachmentExternalId,
    ): WooDecisionUploadAttachment {
        return new WooDecisionUploadAttachment($request->getContent(), $organisationId, $dossierExternalId, $attachmentExternalId);
    }
}
