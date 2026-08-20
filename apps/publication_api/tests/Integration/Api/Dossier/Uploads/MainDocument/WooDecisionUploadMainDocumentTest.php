<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function str_repeat;

final class WooDecisionUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
            'departments' => [$department],
        ]);
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne([
            'dossier' => $wooDecision,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/main-document',
                $organisation->getId(),
                $wooDecision->getExternalId(),
            ),
            dossierId: $wooDecision->getId()->toRfc4122(),
            entityId: $wooDecisionMainDocument->getId()->toRfc4122(),
            entityFileName: $wooDecisionMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadOnPublishedDossierReturnsValidationError(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'status' => DossierStatus::PUBLISHED,
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/main-document',
            $organisation->getId(),
            $wooDecision->getExternalId(),
        ), [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $this->getTestFileContent('1008.pdf'),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'document upload is not allowed for a published dossier']]]);
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/main-document',
            $organisation->getId(),
            $wooDecision->getExternalId(),
        ));
    }

    public function testUploadWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/main-document',
            $organisation->getId(),
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
