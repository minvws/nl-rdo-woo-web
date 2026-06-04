<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class AnnualReportUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $annualReportAttachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/annual-report/E:%s/uploads/attachment/E:%s',
                $organisation->getId(),
                $annualReport->getExternalId(),
                $annualReportAttachment->getExternalId(),
            ),
            dossierId: $annualReport->getId()->toRfc4122(),
            entityId: $annualReportAttachment->getId()->toRfc4122(),
            entityFileName: $annualReportAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $annualReportAttachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/annual-report/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $annualReport->getExternalId(),
            $annualReportAttachment->getExternalId(),
        ));
    }
}
