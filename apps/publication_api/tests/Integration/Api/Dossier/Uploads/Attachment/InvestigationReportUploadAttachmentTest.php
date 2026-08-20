<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function str_repeat;

final class InvestigationReportUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::new()->concept()->create([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/investigation-report/external/%s/uploads/attachment/external/%s',
                $organisation->getId(),
                $investigationReport->getExternalId(),
                $attachment->getExternalId(),
            ),
            dossierId: $investigationReport->getId()->toRfc4122(),
            entityId: $attachment->getId()->toRfc4122(),
            entityFileName: $attachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadOnPublishedDossierReturnsValidationError(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
            'status' => DossierStatus::PUBLISHED,
        ]);
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/investigation-report/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $investigationReport->getExternalId(),
            $attachment->getExternalId(),
        ), [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $this->getTestFileContent('1008.pdf'),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'document upload is not allowed for a published dossier']]]);
    }

    public function testUploadWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/investigation-report/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            str_repeat('x', 129),
            $attachment->getExternalId(),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWithTooLongAttachmentExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/investigation-report/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $investigationReport->getExternalId(),
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
