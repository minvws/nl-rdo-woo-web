<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'WooDecisionDocumentWithdrawRequest',
    description: 'Sends a request to revoke the document of a WooDecision dossier.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}'
                . '/uploads/document/external/{documentExternalId}/withdraw',
            description: 'Revoke the document from a WooDecision dossier.',
            input: WooDecisionDocumentWithdrawRequestDto::class,
            output: false,
            read: false,
            status: Response::HTTP_ACCEPTED,
            name: self::ROUTE_NAME_WITHDRAW,
            processor: WooDecisionDocumentWithdrawProcessor::class,
            openapi: new Operation(
                summary: 'Revoke the document from a WooDecision dossier.',
                description: 'Sends a request to revoke the document of a WooDecision dossier.',
                tags: ['WooDecision'],
            ),
        ),
    ],
    stateless: false,
)]
final readonly class WooDecisionDocumentWithdrawResource
{
    public const string ROUTE_NAME_WITHDRAW = 'woo_decision_document_withdraw';
}
