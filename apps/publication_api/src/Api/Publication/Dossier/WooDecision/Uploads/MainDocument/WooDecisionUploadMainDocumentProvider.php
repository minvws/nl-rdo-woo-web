<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

final class WooDecisionUploadMainDocumentProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WooDecisionUploadMainDocument
    {
        Assert::keyExists($uriVariables, 'organisationId');
        Assert::string($uriVariables['organisationId']);

        Assert::keyExists($uriVariables, 'dossierExternalId');
        Assert::string($uriVariables['dossierExternalId']);

        return new WooDecisionUploadMainDocument(
            content: '',
            organisationId: $uriVariables['organisationId'],
            dossierExternalId: ExternalId::create($uriVariables['dossierExternalId']),
        );
    }
}
