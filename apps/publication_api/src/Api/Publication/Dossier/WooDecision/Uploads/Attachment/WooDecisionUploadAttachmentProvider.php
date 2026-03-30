<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

final class WooDecisionUploadAttachmentProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WooDecisionUploadAttachment
    {
        Assert::keyExists($uriVariables, 'organisationId');
        Assert::string($uriVariables['organisationId']);

        Assert::keyExists($uriVariables, 'dossierExternalId');
        Assert::string($uriVariables['dossierExternalId']);

        Assert::keyExists($uriVariables, 'attachmentExternalId');
        Assert::string($uriVariables['attachmentExternalId']);

        return new WooDecisionUploadAttachment(
            '',
            $uriVariables['organisationId'],
            ExternalId::create($uriVariables['dossierExternalId']),
            ExternalId::create($uriVariables['attachmentExternalId']),
        );
    }
}
