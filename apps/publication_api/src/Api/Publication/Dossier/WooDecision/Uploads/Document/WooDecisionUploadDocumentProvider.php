<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

final class WooDecisionUploadDocumentProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WooDecisionUploadDocument
    {
        Assert::keyExists($uriVariables, 'organisationId');
        Assert::string($uriVariables['organisationId']);

        Assert::keyExists($uriVariables, 'dossierExternalId');
        Assert::string($uriVariables['dossierExternalId']);

        Assert::keyExists($uriVariables, 'documentExternalId');
        Assert::string($uriVariables['documentExternalId']);

        return new WooDecisionUploadDocument(
            '',
            $uriVariables['organisationId'],
            ExternalId::create($uriVariables['dossierExternalId']),
            ExternalId::create($uriVariables['documentExternalId']),
        );
    }
}
